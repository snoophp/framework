<?php

namespace SnooPHP\Model;

use \PDO;

/**
 * Wrapper for mysql database operations
 */
class Db
{
	/**
	 * Perform a generic query statement
	 * 
	 * @param string	$queryString	query string
	 * @param array		$queryParams	query parameters
	 * @param string	$dbName			name of the db configuration (default master)
	 * 
	 * @return array
	 */
	public static function query($queryString, array $queryParams = [], $dbName = "master")
	{
		// Prepare query
		$query = static::instance($dbName)->prepare($queryString);
		foreach ($queryParams as $column => $val) $query->bindValue(is_int($column) ? $column + 1 : ":".$column, $val);

		// Execute
		if ($status = $query->execute())
			return $query->fetchAll(PDO::FETCH_ASSOC);

		return false;
	}

	/**
	 * Begin database transaction
	 * 
	 * @param string $dbName name of the db configuration (default master)
	 * 
	 * @return bool
	 */
	public static function beginTransaction($dbName = "master")
	{
		return static::instance($dbName)->beginTransaction();
	}

	/**
	 * Commit transaction
	 * 
	 * @param string $dbName name of the db configuration (default master)
	 * 
	 * @return bool
	 */
	public static function commit($dbName = "master")
	{
		return static::instance($dbName)->commit();
	}

	/**
	 * Rollback transaction
	 * 
	 * @param string $dbName name of the db configuration (default master)
	 * 
	 * @return bool
	 */
	public static function rollBack($dbName = "master")
	{
		return static::instance($dbName)->rollBack();
	}

	/**
	 * Get or set a connection attribute
	 * 
	 * If attribute value is specified as an array setAttribute is called on its elements
	 * If attribute value is specified setAttribute is called on attribute name
	 * If attribute name is specified as an array getAttribute is called on its elements and an array is returned
	 * If attribute name is specified its value is returned
	 * 
	 * @param int|array		$attributeName	attribute name (eg. ATTR_*)
	 * @param mixed|array	$attributeValue	attribute value
	 * @param string		$dbName 		name of the db configuration (default master)
	 * 
	 * @return bool|array
	 */
	public static function attribute($attributeName, $attributeValue = null, $dbName = "master")
	{
		$db = static::instance($dbName);

		// Set
		if ($attributeValue)
		{
			if (is_array($attributeValue))
			{
				$result = true;
				foreach($attributeValue as $attr => $val) $result &= $db->setAttribute($attr, $val);
				if (!$result) return false;
			}
			else
				if (!$result = $db->setAttribute($attributeName, $attributeValue)) return false;
		}

		// Get
		if (is_array($attributeName))
		{
			$attributes = [];
			foreach ($attributeName as $attribute) $attributes[] = $db->getAttribute($attribute);
			return $attributes;
		}
		else
			return $db->getAttribute($attributeName);
	}

	/**
	 * Get last database error
	 * 
	 * @param string $dbName name of the db configuration (default master)
	 * 
	 * @return array
	 */
	public static function lastError($dbName = "master")
	{
		return static::instance($dbName)->errorInfo();
	}

	/**
	 * Get PDO instance
	 * 
	 * @param string $dbName name of the database configuration
	 * 
	 * @return PDO
	 */
	public static function instance($dbName = "master")
	{
		return $GLOBALS["db".ucwords($dbName)];
	}
}