<?php

namespace SnooPHP\Model;

use ReflectionClass;
use PDO;
use PDOException;

/**
 * A model represents an object of the application
 * Each model has a 1:1 connection with a related db table (e.g. User -> users)
 * 
 * @author sneppy
 */
class Model
{
	/**
	 * @var string $idColumn id column of the table
	 */
	protected static $idColumn = "id";

	/**
	 * @var array $casts list of columns to cast and target type
	 */
	protected static $casts = [
		"id" => "int"
	];

	/**
	 * @var array $autos list of columns that have an auto value on update (i.e. 'modified_at' timestamp)
	 */
	protected static $autos = [
		"created_at",
		"updated_at"
	];

	/**
	 * @var array $casts list of columns to be converted from json
	 */
	protected static $jsons = [];

	/**
	 * Create a new model
	 * 
	 * @param array $values associative array column => value
	 */
	public function __construct(array $values = [])
	{
		foreach ($values as $column => $val) $this->$column = $val;
	}

	/**
	 * Run query on this table and return model
	 * 
	 * @param string	$queryString query string
	 * @param array		$queryParams query parameters
	 * 
	 * @return Collection|bool return collection of results or false if fails
	 */
	public static function select($queryString = "", array $queryParams = [])
	{
		$tableName = static::tableName();

		$rows = Db::query("
			select $tableName.*
			from $tableName
			$queryString
		", $queryParams, static::getDbName());

		if ($rows === false)
			return false;
		if (empty($rows))
			return new Collection([]);

		// Populate models
		$collection = [];
		foreach ($rows as $row)
		{
			// Populate model
			$model = new static;
			foreach ($row as $column => $val) $model->$column = $model->decodeValue($val, $column);
			$collection[] = $model;
		}
		
		return new Collection($collection);
	}

	/**
	 * Find model and return it
	 * 
	 * @param mixed		$id			id of the model
	 * @param string	$idColumn	optional id column different from default $idColumn
	 * 
	 * @return static|bool single model or false if fails
	 */
	public static function find($id, $idColumn = null)
	{
		$tableName = static::tableName();
		$idColumn = $idColumn ?: static::$idColumn;

		$rows = Db::query("
			select *
			from $tableName
			where $idColumn = :id
		", ["id" => $id], static::getDbName());

		if ($rows === false)
			return false;
		if (empty($rows))
			return null;
		
		// Populate model
		$model = new static;
		foreach ($rows[0] as $col => $val) $model->$col = $model->decodeValue($val, $col);
		return $model;
	}

	/**
	 * One-to-one relationship
	 * 
	 * @param string	$forClass	foreign model class name
	 * @param string	$forColumn	foreign column if it differs from className_id (ex. user_id)
	 * 
	 * @return Model|bool foreign model or false if fails
	 */
	public function has($forClass, $forColumn = null)
	{
		$refTable = static::tableName();
		$forTable = $forClass::tableName();
		$refColumn = static::$idColumn;
		$forColumn = $forColumn ?: strtolower(static::modelName())."_id";

		$rows = Db::query("
			select F.*
			from $refTable as R, $forTable as F
			where R.$refColumn = F.$forColumn and R.$refColumn = :id
		", ["id" => $this->$refColumn], static::getDbName());

		if ($rows === false)
			return false;
		if (empty($rows))
			return null;
		
		// Populate model
		$forModel = new $forClass;
		foreach ($rows[0] as $col => $val) $forModel->$col = $forModel->decodeValue($val, $col);
		return $forModel;
	}

	/**
	 * One-to-many relationship
	 * 
	 * @param string	$forClass			foreign model class name
	 * @param string	$forColumn			foreign column if it differs from className_id (ex. user_id)
	 * @param string	$condition			condition to append (AND) to the query (use R and F as Reference and Foreign tables)
	 * @param array		$conditionParams	parameters to bind to the condition query
	 * 
	 * @return Collection|null collection of foreign models
	 */
	public function hasMany($forClass, $forColumn = null, $condition = "", array $conditionParams = [])
	{
		$refTable = static::tableName();
		$forTable = $forClass::tableName();
		$refColumn = static::$idColumn;
		$forColumn = $forColumn ?: strtolower(static::modelName())."_id";

		$rows = Db::query("
			select F.*
			from $refTable as R, $forTable as F
			where R.$refColumn = F.$forColumn and R.$refColumn = :id $condition
		", array_merge(["id" => $this->$refColumn], $conditionParams), static::getDbName());

		if ($rows === false)
			return false;
		if (empty($rows))
			return new Collection([]);

		// Populate models
		$collection = [];
		foreach ($rows as $row)
		{
			$forModel = new $forClass;
			foreach ($row as $column => $val) $forModel->$column = $forModel->decodeValue($val, $column);
			$collection[] = $forModel;
		}
		
		return new Collection($collection);
	}

	/**
	 * One-to-one inverse relationship
	 * 
	 * @param string $refClass reference model class
	 * @param string $forColumn foreign key if different from refTable_id
	 * 
	 * @return Model referenced model or false if fails
	 */
	public function belongsTo($refClass, $forColumn = null)
	{
		// Get columns
		$refTable = $refClass::tableName();
		$forTable = static::tableName();
		$refColumn = $refClass::$idColumn;
		$forColumn = $forColumn ?: strtolower($refClass::modelName())."_id";

		$rows = Db::query("
			select R.*
			from $refTable as R, $forTable as F
			where R.$refColumn = F.$forColumn and F.$forColumn = :id
		", ["id" => $this->$forColumn], static::getDbName());

		if ($rows === false)
			return false;
		if (empty($rows))
			return null;
		
		$refModel = new $refClass;
		foreach ($rows[0] as $col => $val) $refModel->$col = $refModel->decodeValue($val, $col);
		return $refModel;
	}

	/**
	 * Save object to database, updating or inserting a new row
	 * 
	 * @param bool $create for models without an id column this determines if we insert or udpate the model
	 * 
	 * @return static|bool false if fails
	 */
	public function save($create = false)
	{
		// Get model informations
		$tableName	= static::tableName();
		$columns	= static::tableColumns();
		$idColumn	= static::$idColumn;

		$isModel	= false;
		$into		= "";
		$values		= "";
		$updates	= "";
		$condition	= "";
		$params		= [];
		$primaries	= [];
		$updateCondition = "";

		// Remove columns for which no value is specified
		foreach ($columns as $i => $column)
		{
			$name	= $column["column_name"];
			$key	= $column["column_key"];

			// Build query components
			if (property_exists($this, $name) && !in_array($name, static::$autos))
			{
				$into		.= "$name, ";
				$values		.= ":$name, ";
				$updates	.= "$name = :$name, ";
				$condition	.= "$name = :$name and ";
				$params[$name] = $this->encodeValue($name);
			}
			
			// Primary keys used for selecting the correct row in update
			if (strcasecmp($key, "PRI") === 0)
			{
				$updateCondition .= "$name = :$name and ";

				if (property_exists($this, $name) && !in_array($name, static::$autos))
					$primaries[$name] = $this->encodeValue($name);

				// Check if is model with id column
				if ($name === $idColumn) $isModel = true;
			}
		}
		
		// Remove trailing characters
		$into				= substr($into, 0, -2);
		$values				= substr($values, 0, -2);
		$updates			= substr($updates, 0, -2);
		$condition			= substr($condition, 0, -5);
		$updateCondition	= substr($updateCondition, 0, -5);

		try
		{
			// Try to insert model
			$status = Db::query("
				insert into $tableName ($into)
				values ($values)
			", $params, static::getDbName(), false) !== false;
		}
		catch (PDOException $e)
		{
			// If force creation, then bubble up exception
			if ($create) throw $e;

			// Use exception to determine if it was a primary key conflict
			if ($e->getCode() === "23000" && preg_match("/.*'PRIMARY'$/", $e->getMessage()))
			{
				// Try to update model
				$status = Db::query("
					update $tableName
					set $updates
					where $updateCondition
				", $params, static::getDbName(), false) !== false;
			}
			else
				throw $e;
		}

		if ($status)
		{
			// Get last insert id
			$lastInsertId = Db::instance(static::getDbName())->lastInsertId();

			if ($lastInsertId > 0)
				// Fetch with inserted id
				return static::find($lastInsertId);
			else
				// If no last inserted if try to use update condition and primary keys
				return static::select("where $updateCondition", $primaries, static::getDbName())->first();
		}
		else
			return false;
	}

	/**
	 * Delete this model from table
	 * 
	 * @return bool
	 */
	public function delete()
	{
		// Table informations
		$tableName	= static::tableName();
		$columns	= static::tableColumns();
		$idColumn	= static::$idColumn;
		
		// Use id column if possible
		if (isset($this->$idColumn))
		{
			$status = Db::query("
				delete from $tableName
				where $idColumn = :id
			", ["id" => $this->$idColumn], static::getDbName(), false);
		}
		else
		{
			$condition	= "";
			$params		= [];
			foreach ($columns as $column)
			{
				$name	= $column["column_name"];
				$key	= $column["column_key"];
				if (isset($this->$name))
				{
					$condition .= "$name = :$name and ";
					$params[$name] = $this->encodeValue($name);
				}
			}
			$condition = substr($condition, 0, -5);

			var_dump("
				delete from $tableName
				where $condition
			");
			var_dump($params);

			$status = Db::query("
				delete from $tableName
				where $condition
			", $params, static::getDbName(), false);
		}

		return $status !== false && $status > 0;
	}

	/**
	 * Delete all models that match condition
	 * 
	 * @param string	$condition			delete condition
	 * @param array		$conditionParams	condition parameters
	 * 
	 * @return bool|int number of rows deleted or false if fails
	 */
	public static function deleteWhere($condition = "", array $conditionParams = [])
	{
		$tableName = static::tableName();
		
		if (empty($condition))
			return Db::query("delete from $tableName", [], static::getDbName(), false);
		else
			return Db::query("
				delete from $tableName
				where $condition
			", $conditionParams, static::getDbName(), false);
	}


	/**
	 * Purge this model collection
	 * 
	 * @return bool query status
	 */
	public static function purge()
	{
		$tableName = static::tableName();
		return Db::query("truncate $tableName", [], static::getDbName(), false) !== false;
	}

	/**
	 * Reset auto increment value
	 * 
	 * @return bool query status
	 */
	public static function resetAutoIncrement()
	{
		$tableName = static::tableName();
		return Db::query("alter table $tableName auto_increment = 1", [], static::getDbName(), false) !== false;
	}

	/**
	 * Get and/or set the name of the database connection
	 * 
	 * @param string|null $dbName database connection to use
	 * 
	 * @return string current connection
	 */
	public static function getDbName($dbName = null)
	{
		// Return name of current connection
		// If no connection is specified by the class
		// use global defined connection
		// If no global connection is defined
		// use "master"
		return !empty(static::$dbName) ? static::$dbName : ($GLOBALS["defaultDbName"] ?? "master");
	}

	/**
	 * Set database connection to use
	 * 
	 * @param string|null $dbName database connection to use
	 */
	public static function setDbName($dbName = "master")
	{
		static::$dbName = $dbName;
	}

	/**
	 * Return a json representation of the model
	 * 
	 * @return string
	 */
	public function json()
	{
		return to_json($this);
	}
	
	/**
	 * Return the table name
	 * 
	 * @return string
	 */
	public static function tableName()
	{
		// Convert from camel case to underscore
		$cc		= static::modelName();
		$cc[0]	= strtolower($cc[0]);
		return preg_replace_callback("/[A-Z]/", function($uppercase) {

			return "_".strtolower($uppercase[0]);
		}, $cc)."s";
	}

	/**
	 * Return the name of this model
	 * 
	 * @return string
	 */
	protected static function modelName()
	{
		return (new ReflectionClass(get_called_class()))->getShortName();
	}

	/**
	 * Convert value coming from database
	 * 
	 * @param string	$val	value to convert
	 * @param string	$column	name of the column
	 * 
	 * @return mixed
	 */
	protected function decodeValue($val, $column = "")
	{
		if ($column === static::$idColumn)
			$val = (int)$val;
		else if (isset(static::$casts[$column]))
		{
			switch (static::$casts[$column])
			{
				case "object":
					$val = from_json($val, false);
					break;
				case "array":
					$val = from_json($val, true);
					break;
				default:
					settype($val, static::$casts[$column]);
			}
		}
		/// We leave it for compatibility
		else if (in_array($column, static::$jsons) && is_string($val))
			$val = from_json($val);
		
		return $val;
	}

	/**
	 * Get column value
	 * 
	 * @param string $column name of the column
	 * 
	 * @return string
	 */
	protected function encodeValue($column)
	{
		/// @todo for compatibility
		$val = in_array($column, static::$jsons) ? to_json($this->$column) : $this->$column;

		// Convert jsons into valid json strings
		if (isset(static::$casts[$column]) && (static::$casts[$column] === "object" || static::$casts[$column] === "array"))
			$val = to_json($this->$column);

		// Convert bools to ints
		if (is_bool($val)) $val = (int)$val;

		return $val;
	}

	/**
	 * Return name of table columns
	 * 
	 * @return array|false if query fails
	 */
	protected static function tableColumns()
	{
		// Database config
		global $dbConfig;

		$query = Db::instance(static::getDbName())->prepare("
			select column_name, column_key
			from information_schema.columns
			where table_schema = :schema and table_name = :table
		");
		$query->bindValue(":schema", $dbConfig[static::getDbName()]["schema"]);
		$query->bindValue(":table", static::tableName());

		if ($query->execute())
			return $query->fetchAll(PDO::FETCH_ASSOC);
		else
			return false;
	}
}