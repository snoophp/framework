<?php

namespace Model;

use \PDO;

/**
 * Wrapper for mysql database operations
 */
class Db
{
	/**
	 * Perform a generic query statement
	 * 
	 * @param string	$query			query string
	 * @param array		$queryParams	query parameters
	 * 
	 * @return array
	 */
	public static function query($queryString, array $queryParams = [])
	{
		// Prepare query
		$query = static::instance()->prepare($queryString);
		foreach ($queryParams as $column => $val) $query->bindValue(":".$column, $val);

		// Execute
		if ($status = $query->execute())
		{
			return $query->fetchAll(PDO::FETCH_ASSOC);
		}

		return false;
	}

	/**
	 * Begin database transaction
	 * 
	 * @return bool
	 */
	public static function beginTransaction()
	{
		return static::instance()->beginTransaction();
	}

	/**
	 * Commit transaction
	 * 
	 * @return bool
	 */
	public static function commit()
	{
		return static::instance()->commit();
	}

	/**
	 * Rollback transaction
	 * 
	 * @return bool
	 */
	public static function rollBack()
	{
		return static::instance()->rollBack();
	}

	/**
	 * Get or set a connection attribute
	 * 
	 * If attribute value is specified as an array setAttribute is called on its elements
	 * If attribute value is specified setAttribute is called on attribute name
	 * If attribute name is specified as an array getAttribute is called on its elements and an array is returned
	 * If attribute name is specified its value is returned
	 * 
	 * @param string|array	$attributeName	attribute name (eg. ATTR_*)
	 * @param string|array	$attributeValue	attribute value
	 * 
	 * @return bool|array
	 */
	public static function attribute($attributeName, $attributeValue = null)
	{
		$db = static::instance();

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
			{
				if (!$result = $db->setAttribute($attributeName, $attributeValue)) return false;
			}
		}

		// Get
		if (is_array($attributeName))
		{
			$attributes = [];
			foreach ($attributeName as $attribute) $attributes[] = $db->getAttribute($attribute);
			return $attributes;
		}
		else
		{
			return $db->getAttribute($attributeName);
		}
	}

	/**
	 * Get last database error
	 * 
	 * @return array
	 */
	public static function lastError()
	{
		return static::instance()->errorInfo();
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