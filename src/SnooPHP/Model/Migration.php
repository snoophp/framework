<?php

namespace SnooPHP\Model;

use PDO;
use PDOException;

/**
 * Handles migration for a single database
 * 
 * @author Sneppy
 */
class Migration
{
	/**
	 * @var string $dbName name of db connection to use
	 */
	protected $dbName;

	/**
	 * @var Table[] $tables registered tables
	 */
	protected $tables;

	/**
	 * Create a new migration instance
	 * 
	 * @param string	$dbName	db connection name
	 * @param Table[]	$tables	list of tables to register
	 */
	public function __construct($dbName = "master", array $tables = [])
	{
		$this->dbName = $dbName;
		$this->tables = $tables;

		// Compute tables dependencies
		foreach ($tables as $table) $table->generateDependencies();
	}

	/**
	 * Run a migration program
	 * 
	 * @param string $prog program to run ('migrate', 'drop' or 'reset')
	 * 
	 * @return bool program status
	 */
	public function run($prog = "migrate")
	{
		switch (trim($prog))
		{
			case "migrate":
				return $this->migrate();

			case "drop":
				return $this->drop();

			case "reset":
				return $this->drop() & $this->migrate();
			
			default:
				error_log("\n\e[1;31m!\e[0m program $prog not applicable\n");
				return false;
		}
	}

	/**
	 * Register a table
	 * 
	 * @param Table $table
	 */
	public function register(Table $table)
	{
		// Generate table dependencies
		$table->generateDependencies();
		$this->tables[] = $table;
	}

	/**
	 * Run migration
	 * 
	 * @return bool program status
	 */
	protected function migrate()
	{
		// Program status
		$status = true;

		// Compute table dependencies
		// break if circular dependencies are found
		if (($newTables = static::computeDependencies($this->tables)) === false)
		{
			error_log("\e[1;31m!\e[0m circular dependency found; terminating program\n");
			return false;
		}

		// Retrieve last migration
		$migration	= $this->lastMigration();
		$oldTables	= $migration ? unserialize($migration["tables"]) : [];

		// Begin transaction
		// Unfortunately begin transaction has no effect here
		// MySQL adds an implicit commit after DROP TABLE and CREATE TABLE
		printf("\n> starting migration for \e[1;37m'{$this->dbName}'\e[0m database ...\n");
		Db::beginTransaction($this->dbName);

		// Check new tables
		foreach ($newTables as $newTable)
		{
			if (!$newTable->active) continue;
			
			// Find matching table
			$oldTable = null;
			foreach ($oldTables as $oldTab)
				if ($oldTab->name() === $newTable->name())
				{
					$oldTable = $oldTab;
					break;
				}

			// Run migration
			try
			{
				$oldTable ?
				printf("\n> processing existing table \e[1;37m'{$newTable->name()}'\e[0m:\n") :
				printf("\n> creating new table \e[1;37m'{$newTable->name()}'\e[0m:\n{$newTable->createQuery()}\n");
				$newTable->migrate($oldTable, $this->dbName);
				printf("\e[1;32m✓\e[0m all ok\n");
			}
			catch (PDOException $e)
			{
				error_log("\e[1;31m!\e[0m {$e->getMessage()}\n");
				$status = false;
			}
		}

		// Check old tables
		$dropped = $oldTables;
		foreach ($dropped as $i => $t)
			foreach ($newTables as $newTable)
				if ($newTable->name() === $t->name())
				{
					unset($dropped[$i]);
					break;
				}

		if (!empty($dropped))
		{
			// Get table names
			printf("\n> dropping tables:\n");
			foreach ($dropped as $i => $t)
			{
				printf("    ➤ {$t->name()}\n");
				$dropped[$i] = $t->name();
			}

			// Drop tables
			try
			{
				Db::query("drop table if exists ".implode(", ", $dropped), [], $this->dbName, false);
			}
			catch (PDOException $e)
			{
				error_log("\n\e[1;31m!\e[0m {$e->getMessage()}\n");
				$status = false;
			}
		}
		

		if ($status)
		{
			// All ok
			// Create migration table if it does not exist
			$this->createMigrationTable();
			
			// Save migration
			$status = $this->saveMigration($newTables);
			if ($status)
			{
				printf("\n\e[1;32m✓\e[0m migration saved\n");
				Db::commit($this->dbName);
			}
			else
			{

				error_log("\n\e[1;31m!\e[0m error while saving migration; reverting changes ...\n");
				Db::rollBack($this->dbName);
			}
		}
		else
		{
			// Revert changes to database
			// policy is "all or nothing"
			Db::rollBack($this->dbName);
		}

		// Return program status
		return $status;
	}

	/**
	 * Drop all tables migration
	 * 
	 * @return bool program status
	 */
	protected function drop()
	{
		// Program status
		$status = true;

		$migration	= $this->lastMigration();
		$tables		= $migration ? unserialize($migration["tables"]) : [];
		if (empty($tables))
		{
			printf("\n\e[1;32m✓\e[0m all ok, nothing to drop\n");
			return true;
		}

		// Compute table dependencies
		// break if circular dependencies are found
		if (($tables = static::computeDependencies($tables)) === false)
		{
			error_log("\n\e[1;31m!\e[0m circular dependency found; terminating program\n");
			return false;
		}

		// Reverse dependency order
		$tables = array_reverse($tables);

		// Get table names
		printf("\n> dropping tables:\n");
		foreach ($tables as $table)
		{
			printf("    ➤ {$table->name()}\n");
			$tableNames[] = $table->name();
		}

		// Begin transaction and run drop query
		// Unfortunately begin transaction has no effect here
		// MySQL adds an implicit commit after DROP TABLE and CREATE TABLE
		Db::beginTransaction($this->dbName);
		try
		{
			Db::query("drop table if exists ".implode(", ", $tableNames), [], $this->dbName, false);
		}
		catch (PDOException $e)
		{
			error_log("\e[1;31m!\e[0m {$e->getMessage()}\n");
			$status = false;
		}

		if ($status)
		{
			// Drop migration table
			if ($this->dropMigrationTable())
			{
				printf("\e[1;32m✓\e[0m tables dropped\n");
				Db::commit($this->dbName);
			}
			else
			{
				printf("\e[1;31m!\e[0m could not drop migration table; reverting all changes ...\n");
				Db::rollBack($this->dbName);
			}
		}
		else
		{
			printf("\e[1;31m!\e[0m something went wrong ...\n");
			Db::rollBack($this->dbName);
		}

		// Return program status
		return $status;
	}

	/**
	 * Return last migration if any
	 * 
	 * @return object|bool|null null or false if no migration is found
	 */
	protected function lastMigration()
	{
		try
		{
			// Return last migration in temporal time
			$migration = Db::query("select * from migrations order by created_at desc limit 1", [], $this->dbName);
			return $migration[0];
		}
		catch (PDOException $e)
		{
			if ($e->getCode() === "42S02") return null;
		}

		// Something bad :(
		return false;
	}

	/**
	 * Save migration in database
	 * 
	 * @param Table[] $tables current tables
	 * 
	 * @return bool false if fails
	 */
	protected function saveMigration(array $tables)
	{
		try
		{
			$tables = serialize($tables);
			return Db::query("insert into migrations(host, tables) values(?, ?)", [gethostname(), $tables], $this->dbName, false);
		}
		catch (PDOException $e)
		{
			error_log($e->getMessage());
			return false;
		}
	}

	/**
	 * Create migration table if doesn't exists
	 * 
	 * @return bool query status
	 */
	protected function createMigrationTable()
	{
		$migrations = new Table("migrations", true);
		$migrations->string("host")->notNullable()->primaryComposite();
		$migrations->timestamp("created_at")->notNullable()->primaryComposite(true);
		$migrations->blob("tables");
		
		try
		{
			return $migrations->create($this->dbName);
		}
		catch (PDOException $e)
		{
			error_log($e->getMessage());
			return false;
		}
	}

	/**
	 * Drop migration table
	 * 
	 * @return bool query status
	 */
	protected function dropMigrationTable()
	{
		try
		{
			return Db::query("drop table if exists migrations", [], $this->dbName, false);
		}
		catch (PDOException $e)
		{
			error_log($e->getMessage());
			return false;
		}
	}

	/**
	 * Compute table dependencies
	 * 
	 * This method reorders table to avoid dependencies problems
	 * when creating/deleting tables
	 * 
	 * @param Table[] $tables list of tables
	 * 
	 * @return array|bool return reordered list of tables or false if fails
	 */
	protected static function computeDependencies(array $tables)
	{
		/** @todo vulnerable to circular dependencies */
		$result = [];
		while (!empty($tables))
		{
			// Circular dependency check variable
			$num = count($tables);

			foreach ($tables as $i => $table)
				if (!$table->dependent())
				{
					$result[] = $table;

					// Remove from list
					// and free dependent tables
					unset($tables[$i]);
					foreach ($tables as $t) $t->removeDependency($table->name());
				}
			
			// If no dependency-free table is found
			// there is probably a circular dependency
			// nothing we can do about it
			if (count($tables) === $num) return false;
		}

		return $result;
	}
}