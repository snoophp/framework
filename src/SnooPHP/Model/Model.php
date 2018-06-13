<?php

namespace SnooPHP\Model;

use \ReflectionClass;
use \PDO;

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
	 * @return Collection|bool
	 */
	public static function select($queryString = "", array $queryParams = [])
	{
		$rows = Db::query(
			"SELECT ".static::tableName().".* FROM ".static::tableName()." ".$queryString,
			$queryParams,
			static::$dbName
		);

		if ($rows === false)
			return false;
		else if (!count($rows))
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
	 * @return static|null|bool
	 */
	public static function find($id, $idColumn = null)
	{
		$tableName = static::tableName();
		$idColumn = $idColumn ?: static::$idColumn;

		$query = Db::instance(static::$dbName)->prepare("
		SELECT *
		FROM ".$tableName."
		WHERE ".$idColumn." = :id
		");
		$query->bindValue(":id", $id);

		if ($query->execute())
		{
			// Fetch row
			$row = $query->fetch(PDO::FETCH_ASSOC);
			if (!$row) return null;
			
			// Populate model
			$model = new static;
			foreach ($row as $column => $val) $model->$column = $model->decodeValue($val, $column);
			return $model;
		}

		return false;
	}

	/**
	 * One-to-one relationship
	 * 
	 * @param string	$forClass	foreign model class name
	 * @param string	$forColumn	foreign column if it differs from className_id (ex. user_id)
	 * 
	 * @return Model|bool
	 */
	public function has($forClass, $forColumn = null)
	{
		$refTable = static::tableName();
		$forTable = $forClass::tableName();
		$refColumn = static::$idColumn;
		$forColumn = $forColumn ?: strtolower(static::modelName())."_id";

		$query = Db::instance(static::$dbName)->prepare("
		SELECT F.*
		FROM ".$refTable." as R, ".$forTable." as F
		WHERE R.".$refColumn." = F.".$forColumn." AND R.".$refColumn." = :id
		");
		$query->bindValue(":id", $this->$refColumn);

		if ($query->execute())
		{
			// Fetch row
			$row = $query->fetch(PDO::FETCH_ASSOC);
			if (!$row) return null;
			
			// Populate model
			$forModel = new $forClass;
			foreach ($row as $column => $val) $forModel->$column = $forModel->decodeValue($val, $column);
			return $forModel;
		}

		return false;
	}

	/**
	 * One-to-many relationship
	 * 
	 * @param string	$forClass			foreign model class name
	 * @param string	$forColumn			foreign column if it differs from className_id (ex. user_id)
	 * @param string	$condition			condition to append (AND) to the query (use R and F as Reference and Foreign tables)
	 * @param array		$conditionParams	parameters to bind to the condition query
	 * 
	 * @return Collection|null
	 */
	public function hasMany($forClass, $forColumn = null, $condition = "", array $conditionParams = [])
	{
		$refTable = static::tableName();
		$forTable = $forClass::tableName();
		$refColumn = static::$idColumn;
		$forColumn = $forColumn ?: strtolower(static::modelName())."_id";

		$rows = Db::query("
		SELECT F.*
		FROM ".$refTable." as R, ".$forTable." as F
		WHERE R.".$refColumn." = F.".$forColumn." AND R.".$refColumn." = :id ".$condition."
		", array_merge(["id" => $this->$refColumn], $conditionParams), static::$dbName);

		if ($rows === false)
			return false;
		else if (!count($rows))
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
	 * @return Model
	 */
	public function belongsTo($refClass, $forColumn = null)
	{
		// Get columns
		$refTable = $refClass::tableName();
		$forTable = static::tableName();
		$refColumn = $refClass::$idColumn;
		$forColumn = $forColumn ?: strtolower($refClass::modelName())."_id";

		$query = Db::instance(static::$dbName)->prepare("
		SELECT R.*
		FROM ".$refTable." as R, ".$forTable." as F
		WHERE R.".$refColumn." = F.".$forColumn." AND F.".$forColumn." = :id
		");
		$query->bindValue(":id", $this->$forColumn);

		if ($query->execute())
		{
			$row = $query->fetch(PDO::FETCH_ASSOC);
			if (!$row) return null;
			
			// Populate model
			$refModel = new $refClass;
			foreach ($row as $column => $val) $refModel->$column = $refModel->decodeValue($val, $column);
			return $refModel;
		}

		return false;
	}

	/**
	 * Save object to database, updating or inserting a new row
	 * 
	 * @param bool|null $update if true update model, otherwise insert (null, decide based on id column)
	 * 
	 * @return Model
	 */
	public function save($update = null)
	{
		// Global instance
		global $db;

		// Get columns
		$columns = static::tableColumns();
		$idColumn = static::$idColumn;
		$update = (isset($this->$idColumn) && $update === null) || $update;

		if ($update)
		{
			// Build updates
			$updates = "";
			foreach ($columns as $column)
				if ($column !== $idColumn && !(in_array($column, static::$autos) && $this->$column === null))
					$updates .= " ".$column." = :".$column.",";
			$updates = substr($updates, 1, -1);

			// Update query
			$query = Db::instance(static::$dbName)->prepare("
			UPDATE ".static::tableName()."
			SET ".$updates."
			WHERE ".$idColumn." = :id
			");
			foreach ($columns as $column)
				if ($column !== $idColumn && !(in_array($column, static::$autos) && $this->$column === null))
					$query->bindValue(":".$column, $this->encodeValue($column));
			$query->bindValue(":id", $this->$idColumn);

			if ($query->execute()) return $this;

			return false;
		}
		else
		{
			// Build values and into
			$values = ""; $into = "";
			foreach ($columns as $column)
			{
				if ($column !== $idColumn && isset($this->$column))
				{
					$into .= " ".$column.",";
					$values .= " :".$column.",";
				}
			}
			$into = substr($into, 1, -1);
			$values = substr($values, 1, -1);

			// Insert query
			$query = Db::instance(static::$dbName)->prepare("
			INSERT INTO ".static::tableName()."(".$into.")
			VALUES (".$values.")
			");
			foreach ($columns as $column)
				if ($column !== $idColumn && isset($this->$column)) $query->bindValue(":".$column, $this->encodeValue($column));

			if ($query->execute())
			{
				// Set id
				$this->$idColumn = $db->lastInsertId();
				return static::find($this->$idColumn);
			}
			
			return false;
		}
	}

	/**
	 * Delete this model from table
	 * 
	 * @return bool
	 */
	public function delete()
	{
		$idColumn = static::$idColumn;
		if (!isset($this->$idColumn)) return false;
		
		$query = Db::instance(static::$dbName)->prepare("
		DELETE FROM ".static::tableName()."
		WHERE ".static::$idColumn." = :id
		");
		$query->bindValue(":id", $this->$idColumn);

		if ($query->execute())
		{
			return true;
		}

		return false;
	}

	/**
	 * Delete all models that match condition
	 * 
	 * @param string	$condition if not specified purge() is called
	 * @param array		$conditionParams condition parameters
	 * 
	 * @return bool|int
	 */
	public static function deleteWhere($condition = null, array $conditionParams = [])
	{
		if (!$condition) return static::purge();

		$query = Db::instance(static::$dbName)->prepare("
		DELETE FROM ".static::tableName()."
		WHERE ".$condition."
		");
		foreach ($conditionParams as $column => $val) $query->bindValue(is_int($column) ? $column + 1 : ":".$column, $val ?: null);

		if ($query->execute()) return $query->rowCount();

		return false;
	}


	/**
	 * Purge this model collection
	 * 
	 * @return bool
	 */
	public static function purge()
	{
		$query = Db::instance(static::$dbName)->prepare("
		TRUNCATE ".static::tableName()."
		");
		
		// Run purge query
		if ($query->execute()) return true;

		return false;
	}

	/**
	 * Reset auto increment value
	 * 
	 * @return bool
	 */
	public static function resetAutoIncrement()
	{
		$tableName = static::tableName();
		return Db::query("alter table $tableName AUTO_INCREMENT = 1", [], static::$dbName) !== false;
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
	 * Return a string representation of the model
	 * 
	 * @return string
	 */
	public function toString()
	{
		$idColumn = static::$idColumn;
		$out = static::modelName()." #".$this->$idColumn.":\n";
		foreach (get_object_vars($this) as $var => $val)
			if (isset($this->$var) && $this->$var) $out.= " - ".$var." = ".print_r($val, true)."\n";
		
		return $out;
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
		if (isset(static::$casts[$column]))	settype($val, static::$casts[$column]);
		if (in_array($column, static::$jsons) && is_string($val)) $val = json_decode(unescape_unicode($val));
		return $val;
	}

	/**
	 * Convert column to string
	 * 
	 * @param mixed $column name of the column
	 * 
	 * @return string
	 */
	protected function encodeValue($column)
	{
		return !isset($this->$column) ? null : (
			in_array($column, static::$jsons) ? escape_unicode(json_encode($this->$column)) : (
				$this->$column
			)
		);
	}

	/**
	 * Return name of table columns
	 * 
	 * @return string[]|null
	 */
	protected static function tableColumns()
	{
		// Global instance
		global $dbConfig;

		$query = Db::instance(static::$dbName)->prepare("
		SELECT COLUMN_NAME
		FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :tableName
		");
		$query->bindValue(":schema", $dbConfig[static::$dbName]["schema"]);
		$query->bindValue(":tableName", static::tableName());

		if ($query->execute())
		{
			$rows = $query->fetchAll(PDO::FETCH_NUM);

			// Populate array
			$columns = [];
			foreach ($rows as $row)
				$columns[] = $row[0];
			
			return $columns;
		}

		return null;
	}
}