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
	protected static $casts = [];

	/**
	 * @var array $casts list of columns to be converted from json
	 */
	protected static $jsons = [];

	/**
	 * @var array $autos list of columns that have an auto value on update (i.e. 'modified_at' timestamp)
	 */
	protected static $autos = [];

	/**
	 * @var string $dbName name of the database @todo not implemented yet
	 */
	protected static $dbName = "master";

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
		", $queryParams, static::$dbName);

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
		", ["id" => $id], static::$dbName);

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
		", ["id" => $this->$refColumn], static::$dbName);

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
		", array_merge(["id" => $this->$refColumn], $conditionParams), static::$dbName);

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
		", ["id" => $this->$forColumn], static::$dbName);

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
			$status = Db::query("
				insert into $tableName ($into)
				values ($values)
			", $params, static::$dbName, false) !== false;
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
				", $params, static::$dbName, false) !== false;
			}
			else
				throw $e;
		}

		if ($status)
		{
			// Get last insert id
			$lastInsertId = Db::instance(static::$dbName)->lastInsertId();

			if ($lastInsertId > 0)
				return static::find($lastInsertId);
			else
				return static::select("where $condition", $params, static::$dbName)->first();
		}
		else
			return false;
		
		// Update model with id column
		if (isset($this->$idColumn))
			$status = Db::query("
				update $tableName
				set $updates
				where $idColumn = :$idColumn
			", $params, static::$dbName, false) !== false;
		
		// Create model with id column
		else if ($isModel)
			$status = Db::query("
				insert into $tableName ($into)
				values ($values)
			", $params, static::$dbName, false) !== false;

		// Create generic model
		else if ($create)
			$status = Db::query("
				insert into $tableName ($into)
				values ($values)
			", $params, static::$dbName, false) !== false;

		// Update generic model
		else
			$status = Db::query("
				update $tableName
				set $updates
				where $updateCondition
			", $params, static::$dbName, false) !== false;

		if ($status)
		{
			// Try to fetch model with id
			if ($isModel)
			{
				// Set id and fetch
				$this->$idColumn = Db::instance(static::$dbName)->lastInsertId();
				return static::find($this->$idColumn);
			}
			else
			{
				return static::select("where $condition", $params, static::$dbName)->first();
			}
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
			", ["id" => $this->$idColumn], static::$dbName, false);
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
			", $params, static::$dbName, false);
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
			return Db::query("delete from $tableName", [], static::$dbName, false);
		else
			return Db::query("
				delete from $tableName
				where $condition
			", $conditionParams, static::$dbName, false);
	}


	/**
	 * Purge this model collection
	 * 
	 * @return bool query status
	 */
	public static function purge()
	{
		$tableName = static::tableName();
		return Db::query("truncate $tableName", [], static::$dbName, false) !== false;
	}

	/**
	 * Reset auto increment value
	 * 
	 * @return bool query status
	 */
	public static function resetAutoIncrement()
	{
		$tableName = static::tableName();
		return Db::query("alter table $tableName auto_increment = 1", [], static::$dbName, false) !== false;
	}

	/**
	 * Set database connection to use
	 * 
	 * @param string $dbName database connection to use
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
		if (isset(static::$casts[$column]))
			settype($val, static::$casts[$column]);
		if (in_array($column, static::$jsons) && is_string($val))
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
		$val = in_array($column, static::$jsons) ? to_json($this->$column) : $this->$column;
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

		$query = Db::instance(static::$dbName)->prepare("
			select column_name, column_key
			from information_schema.columns
			where table_schema = :schema and table_name = :table
		");
		$query->bindValue(":schema", $dbConfig[static::$dbName]["schema"]);
		$query->bindValue(":table", static::tableName());

		if ($query->execute())
			return $query->fetchAll(PDO::FETCH_ASSOC);
		else
			return false;
	}
}