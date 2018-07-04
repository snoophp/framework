<?php

namespace SnooPHP\Model;

use PDO;

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
	 * @param bool		$fetchResults	if true (defaul behaviour) return query results, otherwise just true/false
	 * 
	 * @return array|bool|int return query results or number of rows affected or false if fails
	 */
	public static function query($queryString, array $queryParams = [], $dbName = "master", $fetchResults = true)
	{
		// Prepare query
		$query = static::instance($dbName)->prepare($queryString);
		foreach ($queryParams as $column => $val) $query->bindValue(is_int($column) ? $column + 1 : ":".$column, $val);

		// Execute
		if ($query->execute())
			return $fetchResults ?
			$query->fetchAll(PDO::FETCH_ASSOC) :
			$query->rowCount();
		
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