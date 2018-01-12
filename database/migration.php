<?php

use Model\Db;
use Model\Table;

/******************
 * MIGRATION SCRIPT
 ******************/

/**
 * @var Table[] $tables list of tables registered for migration
 */
$tables = [];

/**
 * Run migration on table set
 * 
 * The migration script compare tables with previous migration.
 * If a migration associated with the current table exists
 * changes are computed and applied, otherwise a new table is created.
 * In any case the new table is serialized and saved as a migration file
 * or an error is output.
 * 
 * @param string $schema schema to use
 */
function migrate_all($schema = "master")
{
	global $tables;

	// Compute dependencies to avoid conflicts
	$tables = compute_dependencies($tables);

	foreach($tables as $table)
	{
		$name			= $table->name();
		$migrationFile	= __DIR__."/migrated/".$schema."/".$name.".tab";
		$status			= true;

		echo "\n=== ".strtoupper($name)." ===\n";

		if (file_exists($migrationFile))
		{
			echo "processing existing table '".$name."':\n";

			$migration = unserialize(file_get_contents($migrationFile));
			$status = process_table($table, $migration);
		}
		else
		{
			echo "creating new table '".$name."':\n";
			echo "\n".$table->createQuery()."\n\n";
			
			$status = $table->create();
		}

		if ($status)
		{
			echo "all ok ... saving migration\n";

			$data = serialize($table);
			if (!file_exists(dirname($migrationFile))) mkdir(dirname($migrationFile));
			file_put_contents($migrationFile, $data);
		}
		else
		{
			echo "something went wrong, check table definition\n";
		}
	}
}

/**
 * Drop all tables
 * 
 * @param string $schema schema to use
 * 
 * @return bool false if fails
 */
function drop_all($schema = "master")
{
	// Drop existing tables
	$migrationFiles = glob(__DIR__."/migrated/".$schema."/*.tab");

	if (count($migrationFiles) > 0)
	{
		$migrations = [];
		foreach($migrationFiles as $migrationFile)
		{
			$migration = unserialize(file_get_contents($migrationFile));
			$migration->computeDependencies();
			$migrations[] = $migration;
		}

		// Compute reverse dependencies
		$migrations = array_reverse(compute_dependencies($migrations));

		echo "dropping tables ...\n";

		foreach($migrations as $migration)
		{
			echo " # ".$migration->name()."\n";

			$query[] = $migration->name();
		}
		$status = Db::query("drop table if exists ".implode(", ", $query)) !== false;

		if ($status)
		{
			// Remove migration
			foreach($migrationFiles as $migrationFile) unlink($migrationFile);

			echo "tables dropped\n\n";
			return true;
		}
		else
		{
			echo "something went wrong ...\n";
			return false;
		}
	}
	
	return true;
}

/**
 * Drop all and migrate
 * 
 * @param string $schema schema to use
 */
function reset_all($schema = "master")
{
	// Drop and migrate
	if (drop_all($schema)) migrate_all($schema);
}

/**
 * Process an existing table
 * 
 * Computes difference between current table and last migration
 * 
 * @param Table	$newTable	new table
 * @param Table $oldTable	table from last migration
 */
function process_table(Table $newTable, Table $oldTable)
{
	if ($newTable->name() !== $oldTable->name()) return false;

	$tableName	= $newTable->name();
	$newColumns	= $newTable->columns();
	$oldColumns	= $oldTable->columns();
	$statements	= [];
	$status		= true;

	// Added or modified columns
	foreach ($newColumns as $newColumn)
	{
		$new		= true;
		$changed	= true;
		foreach ($oldColumns as $oldColumn)
		{
			if ($oldColumn->name() === $newColumn->name())
			{
				$new = false;

				// Check if changed
				$changed = $newColumn != $oldColumn;

				break;
			}
		}

		if ($new)
		{
			$name = $newColumn->name();

			echo " # creating column ".$name."\n";

			// New column, generate add column statement
			$query 	= "add column ".$newColumn->declaration();

			// Add constraints
			$constraints = [];
			if ($newColumn->property("unique")) $constraints[] = "add constraint PK_".$tableName."_".$name." unique key (".$name.")";
			if ($newColumn->property("primary")) $constraints[] = "add constraint PK_".$tableName."_".$name." primary key (".$name.")";
			if ($newColumn->property("foreign")) $constraints[] = "add constraint FK_".$tableName."_".$name." foreign key (".$name.") references ".$foreign["table"]."(".$foreign["column"].")".($foreign["onDelete"] ? " on delete ".$foreign["onDelete"] : "").($foreign["onUpdate"] ? " on update ".$foreign["onUpdate"] : "");
			
			$status &= Db::query("alter table ".$newTable->name()." ".implode(", ", array_merge([$query], $constraints))) !== false;
		}
		else if ($changed)
		{
			$name = $newColumn->name();

			echo " # changing column ".$newColumn->name()."\n";

			// Drop old constraints
			$constraints = [];
			if ($oldColumn->property("unique")) $constraints[] = "drop unique key UK_".$tableName."_".$oldColumn->name();
			if ($oldColumn->property("primary")) $constraints[] = "drop primary key PK_".$tableName."_".$oldColumn->name();
			if ($oldColumn->property("foreign")) $constraints[] = "drop foreign key FK_".$tableName."_".$oldColumn->name();

			$status &= Db::query("alter table ".$newTable->name()." ".implode(", ", $constraints)) !== false;

			// New column, generate change column statement
			$query = $newColumn->declaration();
			
			// Add constraints
			$constraints = [];
			if ($newColumn->property("unique")) $constraints[] = "add constraint PK_".$tableName."_".$name." unique key (".$name.")";
			if ($newColumn->property("primary")) $constraints[] = "add constraint PK_".$tableName."_".$name." primary key (".$name.")";
			if ($newColumn->property("foreign")) $constraints[] = "add constraint FK_".$tableName."_".$name." foreign key (".$name.") references ".$foreign["table"]."(".$foreign["column"].")".($foreign["onDelete"] ? " on delete ".$foreign["onDelete"] : "").($foreign["onUpdate"] ? " on update ".$foreign["onUpdate"] : "");
			
			$status &= Db::query("alter table ".$newTable->name()." ".implode(", ", array_merge([$query], $constraints))) !== false;
		}
	}

	// Dropped columns
	foreach ($oldColumns as $oldColumn)
	{
		$dropped = true;
		foreach ($newColumns as $newColumn)
		{
			if ($newColumn->name() === $oldColumn->name())
			{
				$dropped = false;
				break;
			}
		}

		if ($dropped)
		{
			echo " # dropping column ".$oldColumn->name()."\n";

			// Dropped column, generate drop column statement
			$query 	= "drop column ".$oldColumn->name();

			// Drop constraints
			$constraints = [];
			if ($oldColumn->property("unique")) $constraints[] = "drop unique key UK_".$tableName."_".$oldColumn->name();
			if ($oldColumn->property("primary")) $constraints[] = "drop primary key PK_".$tableName."_".$oldColumn->name();
			if ($oldColumn->property("foreign")) $constraints[] = "drop foreign key FK_".$tableName."_".$oldColumn->name();
			
			$status &= Db::query("alter table ".$newTable->name()." ".implode(", ", array_merge([$query], $constraints))) !== false;
		}
	}

	return true;
}

/**
 * Compute migration order to avoid collisions between tables
 * 
 * This function takes into account table dependencies
 * and reorder the table list in order to avoid collisions between tables.
 * Errors are usually generated by foreign key constraints
 * that reference tables non created yet.
 * Circular dependency or missing references may result in an infinite loop.
 * 
 * @param array $tables tables to reorder;
 * 
 * @return array ordered tables
 */
function compute_dependencies(array $tables)
{
	echo "computing tables dependencies ...\n";

	/**
	 * @todo should check for missing references and circular dependencies
	 */

	$orders = [];
	while (!empty($tables))
	{
		// Move tables that are dependency free in order list
		foreach ($tables as $i => $table)
		{
			if (!$table->dependent())
			{
				$orders[] = $table;

				unset($tables[$i]);
				foreach ($tables as $t) $t->removeDependency($table->name());
			}
		}
	}

	echo "dependencies computed!\n";

	return $orders;
}

/**
 * Add table to list and compute its dependencies
 * 
 * @param Table $table table to add
 */
function register_table(Table $table)
{
	global $tables ;
	$table->computeDependencies();
	$tables[] = $table;
}