<?php

namespace SnooPHP\Model;

/**
 * A table is used to create a migration plan
 * 
 * @author sneppy
 */
class Table
{
	/**
	 * @var bool $active if true table is considered active during migration
	 * 
	 * Migration will delete any table that is no more registered
	 * If you want to 'deactivate' a table temporarily instead, set this flag to false
	 */
	public $active = true;

	/**
	 * @var string $name table name
	 */
	protected $name;

	/**
	 * @var array $columns array of columns
	 */
	protected $columns = [];

	/**
	 * @var array $dependencies list of table dependencies
	 */
	protected $dependencies = [];

	/**
	 * @var bool $ignoreIfExists if true and table already exists, trying to create it again will not generate errors or warnings
	 */
	protected $ignoreIfExists;

	/**
	 * Create a new table (doesn't run CREATE TABLE)
	 * 
	 * @param string	$model			class of the model or name of the table
	 * @param bool		$ignoreIfExists	create table only if it doesn't already exists
	 */
	public function __construct($model, $ignoreIfExists = false)
	{
		$this->name				= class_exists($model) ? $model::tableName() : $model;
		$this->ignoreIfExists	= $ignoreIfExists;
	}

	/**
	 * Get name
	 * 
	 * @return string
	 */
	public function name()
	{
		return $this->name;
	}

	/**
	 * Get columns
	 * 
	 * @return Column[]
	 */
	public function columns()
	{
		return $this->columns;
	}

	/**
	 * Returns true if depends on other tables
	 * 
	 * @return bool
	 */
	public function dependent()
	{
		return count($this->dependencies) > 0;
	}

	///////////////// BEGIN COLUMNS /////////////////

	/**
	 * Add an auto increment primary column
	 * 
	 * @param string	$name	column name (default id)
	 * @param int		$size	integer size (default 16)
	 */
	public function id($name = "id", $size = 16)
	{
		$this->add($name, "int")->size($size)->unsigned()->autoIncrement()->primary();
	}

	/**
	 * Adds created and modified timestamps
	 */
	public function timestamps()
	{
		$this->timestamp("created_at");
		$this->timestamp("updated_at")->onUpdate();
	}

	/**
	 * Add a string (varchar) column with an optional size
	 * 
	 * @param string	$name	column name
	 * @param int		$size	optional size
	 * 
	 * @return Column
	 */
	public function string($name, $size = 255)
	{
		return $this->add($name, "varchar")->size($size);
	}

	/**
	 * Add an integer column with an optional size
	 * 
	 * @param string	$name	column name
	 * @param int		$size	optional size
	 * 
	 * @return Column
	 */
	public function integer($name, $size = 16)
	{
		return $this->add($name, "int")->size($size);
	}

	/**
	 * Alias for integer
	 * 
	 * @see integer()
	 */
	public function int($name, $size = 16)
	{
		return $this->integer($name, $size);
	}

	/**
	 * Add an unsigned int column with an optional size
	 * 
	 * @param string	$name	column name
	 * @param int		$size	optional size
	 * 
	 * @return Column
	 */
	public function uint($name, $size = 16)
	{
		return $this->add($name, "int")->size($size)->unsigned();
	}

	/**
	 * Add a decimal column
	 * 
	 * @param string	$name		column name
	 * @param int		$size		total number of digits
	 * @param int		$precision	number of decimal digits
	 * 
	 * @return Column
	 */
	public function decimal($name, $size = 11, $precision = 2)
	{
		return $this->add($name, "decimal")->size("$size, $precision");
	}

	/**
	 * Add a boolean column (using TINYINT(1))
	 * 
	 * @param string	$name	column name
	 * 
	 * @return Column
	 */
	public function bool($name)
	{
		return $this->add($name, "bool");
	}

	/**
	 * Add a timestamp column
	 * 
	 * @param string $name column name
	 * 
	 * @return Column
	 */
	public function timestamp($name)
	{
		return $this->add($name, "timestamp")->default("current_timestamp");
	}

	/**
	 * Add a json column
	 * 
	 * @param string $name column name
	 * 
	 * @return Column
	 */
	public function json($name)
	{
		return $this->add($name, "json");
	}

	/**
	 * Add a text column
	 * 
	 * @param string $name column name
	 * 
	 * @return Column
	 */
	public function text($name)
	{
		return $this->add($name, "text");
	}

	/**
	 * Add a blob column
	 * 
	 * @param string $name column name
	 * 
	 * @return Column
	 */
	public function blob($name)
	{
		return $this->add($name, "blob");
	}

	/**
	 * Add column to table
	 * 
	 * @param string	$name	column name
	 * @param string	$type	column type
	 * 
	 * @return Column added column
	 */
	public function add($name, $type)
	{
		$column = new Column($name, $type);
		$this->columns[] = $column;
		return $column;
	}

	///////////////// END COLUMNS /////////////////

	/**
	 * Generate table data (i.e. columns and constraints) from table description
	 * 
	 * Note that it's a WIP, not all standard features
	 * are supported
	 * 
	 * @param string $tableDesc table description
	 */
	public function generate($tableDesc)
	{
		// Table description is analyzed line-by-line
		$lfcr = "\r\n";
		$line = strtok($tableDesc, $lfcr);

		while ($line)
		{
			// Run regex
			if (preg_match("/^^\s*(?<name>\w*)(?<required>\*)?\s*(?:->\s*(?<ref_class>[\w\\\\]+)(?:::(?<ref_column>\w+))?(?:\(\s*(?<on_delete>[\w\ ]+)\s*(?:,\s*(?<on_update>[\w\ ]+))?\s*\))?)?(?:\s+(?<key_mod>[PU])(?<key_comp>[\+;])?)?\s*:\s*(?<type>\w+)(?:\((?<size>\d+)\))?(?:\s*=\s*(?<default>(?:\".*\")|'.*'|(?:\w+\s*)+))?\s*[,;]?/", trim($line), $matches))
			{
				// Required fields are name and type
				$name		= $matches["name"];
				$required	= $matches["required"] ? true : null;
				$refClass	= $matches["ref_class"] ?? null;
				$refColumn	= $matches["ref_column"] ?? null;
				$onDelete	= $matches["on_delete"] ?? null;
				$onUpdate	= $matches["on_update"] ?? null;
				$keyMod		= $matches["key_mod"] ?? null;
				$keyComp	= $matches["key_comp"] ?? null;
				$type		= $matches["type"];
				$size		= $matches["size"] ?? null;
				$default	= $matches["default"] ?? null;

				// Special cases first
				if ($type === "id")
					$this->id($name ?: "id", $size ?: 16);

				else if ($type === "timestamps")
					$this->timestamps();

				else if (method_exists($this, $type))
				{
					// Create default column
					$col = $this->{$type}($name);

					// Is required?
					if ($required)
						$col->notNullable();

					// Type size
					if ($size)
						$col->size($size);

					// Default value
					if ($default)
						$col->default($default);
					
					// Reference constraint
					if ($refClass)
						$col->references($refClass, $refColumn ?: "id", $onDelete ?: "no action", $onUpdate ?: "no action");

					// Primary and unique key constraints
					if ($keyMod)
					{
						switch ($keyMod)
						{
							case 'P':
							{
								if (!$keyComp)
									$col->primary();
								else if ($keyComp === '+')
									$col->primaryComposite(false);
								else if ($keyComp === ';')
									$col->primaryComposite(true);
								
								break;
							}

							case 'U':
							{
								if (!$keyComp)
									$col->unique();
								else if ($keyComp === '+')
									$col->uniqueComposite(false);
								else if ($keyComp === ';')
									$col->uniqueComposite(true);

								break;
							}
						}
					}
				}
				else
					error_log("type {$type} is not supported");
			}

			// Next line
			$line = strtok($lfcr);
		}
	}

	/**
	 * Compute dependencies
	 * 
	 * Compute dependencies based on column
	 * Used to build dependency treea and compute migration order
	 * 
	 * @return array
	 */
	public function generateDependencies()
	{
		$this->dependencies = [];
		foreach ($this->columns as $column)
		{
			// Dependencies in most cases are generated by foreign keys
			if (($foreign = $column->property("foreign")) !== null)
				$this->dependencies[] = $foreign["table"];
			if (($foreign = $column->property("foreignComposite")) !== null && $foreign["closeChain"])
				$this->dependencies[] = $foreign["table"];
		}

		// Remove duplicate dependencies
		$this->dependencies = array_unique($this->dependencies);
		return $this->dependencies;
	}

	/**
	 * Remove satisfied dependency and return new list
	 * 
	 * @param string $table dependency to remove
	 * 
	 * @return array
	 */
	public function removeDependency($table)
	{
		foreach ($this->dependencies as $i => $dependency)
			if ($dependency === $table)
			{
				// Unset dependency
				unset($this->dependencies[$i]);
				break;
			}

		return $this->dependencies;
	}

	/**
	 * Get query string
	 * 
	 * @return string
	 */
	public function createQuery()
	{
		// The final query we execute
		$query			= "create table ".($this->ignoreIfExists ? "if not exists " : "")."{$this->name}(\n\t";

		// Parse columns and constraints
		$columns		= [];
		$constraints	= [];
		foreach ($this->columns as $column)
		{
			$name = $column->name();

			// Add column declaration
			// e.g. 'name VARCHAR(255) NOT NULL'
			$columns[] = $column->declaration();

			// Compute non-composite key constraints
			if ($column->property("unique"))				// UNIQUE
				$constraints[] = "constraint UK_{$this->name()}_$name unique key ($name)";
			if ($column->property("primary"))				// PRIMARY
				$constraints[] = "constraint PK_{$this->name()}_$name primary key ($name)";
			if ($foreign = $column->property("foreign"))	// FOREIGN
				$constraints[] = "constraint FK_{$this->name()}_$name foreign key ($name) references ".$foreign["table"]."(".$foreign["column"].")".($foreign["onDelete"] ? " on delete ".$foreign["onDelete"] : "").($foreign["onUpdate"] ? " on update ".$foreign["onUpdate"] : "");

			// Composite keys
			if ($column->property("uniqueComposite") !== null)		// UNIQUE
			{
				$uniqueChain[] = $column;
				// Close chain
				if ($column->property("uniqueComposite") === true)
				{
					$compositeKey = array_map(function($column) {
	
						return $column->name();
					}, $uniqueChain);
	
					$constraints[] = "constraint UK_{$this->name}_".implode("_", $compositeKey)." unique key (".implode(", ", $compositeKey).")";
					$uniqueChain = [];
				}
			}
			if ($column->property("primaryComposite") !== null)		// PRIMARY
			{
				$primaryChain[] = $column;
				// Close chain
				if ($column->property("primaryComposite") === true)
				{
					$compositeKey = array_map(function($column) {
	
						return $column->name();
					}, $primaryChain);
	
					$constraints[] = "constraint primary key (".implode(", ", $compositeKey).")";
					$primaryChain = [];

					// Extra care when creating primary key
					// an index may be required by external tables
					// that references this one, thus we create a surrogate index
					$constraints[] = "index PK_{$this->name}_".implode("_", $compositeKey)." (".implode(", ", $compositeKey).")";
				}
			}
			if (($foreign = $column->property("foreignComposite")) !== null)	// FOREIGN
			{
				$foreignChain[] = $column;
				// Close chain
				if ($foreign["closeChain"] === true)
				{
					$compositeKey = [];
					$compositeRef = [];
					foreach ($foreignChain as $column)
					{
						$compositeKey[] = $column->name();
						$compositeRef[] = $column->property("foreignComposite")["column"];
					}
	
					$onDelete = $foreign["onDelete"];
					$onUpdate = $foreign["onUpdate"];
					$refTable = $foreign["table"];
	
					$constraints[] = "constraint FK_{$this->name}_".implode("_", $compositeKey)." foreign key (".implode(", ", $compositeKey).") references $refTable(".implode(", ", $compositeRef).") on delete $onDelete on update $onUpdate";
					$foreignChain = [];
				}
			}
		}
		
		// Build query
		return $query.implode(",\n\t", array_merge($columns, $constraints))."\n);";
	}

	/**
	 * Attemp to create a table with 'create table' instruction
	 * 
	 * @param string $dbName schema name
	 * 
	 * @return bool false if fails
	 */
	public function create($dbName = "master")
	{
		return Db::query($this->createQuery(), [], $dbName, false) !== false;
	}

	/**
	 * Drop the table
	 * 
	 * @param string $dbName schema name
	 * 
	 * @return bool false if fails
	 */
	public function drop($dbName = "master")
	{
		return Db::query("drop table if exists ".$this->name, [], $dbName, false) !== false;
	}

	/**
	 * Run migration for this table
	 * 
	 * @param Table		$old	old table
	 * @param string	$dbName	schema name
	 * 
	 * @return bool false if migration failed
	 */
	public function migrate(Table $old = null, $dbName = "master")
	{
		// If no old table is specified try to create table
		if (!$old) return $this->create($dbName);

		// Assert that table matches
		if ($this->name() !== $old->name()) return false;

		// Status and columns
		$status		= true;
		$newColumns	= $this->columns();
		$oldColumns = $old->columns();

		// Drop constraints
		$constraints = [];
		foreach ($oldColumns as $oldColumn)
		{
			// Drop non-composite keys
			if ($oldColumn->property("foreign"))
				$constraints[] = "drop foreign key FK_{$this->name}_{$oldColumn->name()}";
			if ($oldColumn->property("primary") && !$oldColumn->property("autoIncrement"))
				$constraints[] = "drop primary key";
			if ($oldColumn->property("unique"))
				$constraints[] = "drop index UK_{$this->name}_{$oldColumn->name()}";

			// Drop composite keys
			if (($foreign = $oldColumn->property("foreignComposite")) !== null)	// FOREIGN
			{
				$foreignChain[] = $oldColumn->name();
				// Close chain and reset
				if ($foreign["closeChain"] === true)
				{
					$constraints[] = "drop foreign key FK_{$this->name}_".implode("_", $foreignChain);
					$foreignChain = [];
				}
			}

			if ($oldColumn->property("primaryComposite") !== null)	// PRIMARY
			{
				$primaryChain[] = $oldColumn->name();
				// Close chain and reset
				if ($oldColumn->property("primaryComposite") === true)
				{
					$constraints[] = "drop primary key";
					$primaryChain = [];
				}
			}

			if ($oldColumn->property("uniqueComposite") !== null)	// UNIQUE
			{
				$uniqueChain[] = $oldColumn->name();
				// Close chain and reset
				if ($oldColumn->property("uniqueComposite") === true)
				{
					$constraints[] = "drop index UK_{$this->name}_".implode("_", $uniqueChain);
					$uniqueChain = [];
				}
			}
		}
		// Run query on constraints (if any)
		if (!empty($constraints))
			$status &= Db::query("alter table {$this->name} ".implode(", ", $constraints), [], $dbName, false) !== false;
		
		// Reset chains
		$foreignChain	= [];
		$primaryChain	= [];
		$uniqueChain	= [];

		// Added or modified columns
		foreach ($newColumns as $newColumn)
		{
			// Determine if column was added or changed
			$new		= true;
			$changed	= true;
			foreach ($oldColumns as $oldColumn)
				if ($oldColumn->name() === $newColumn->name())
				{
					$new		= false;
					$changed	= !($newColumn == $oldColumn);
					break;
				}
			
			if ($new)
			{
				printf("    ➤ creating column \e[1;37m{$newColumn->name()}\e[0m\n");

				// New column query
				$query	= "alter table {$this->name} add column {$newColumn->declaration()}";
				if ($newColumn->property("primary") && $newColumn->property("autoIncrement"))
					$query .= " primary key";
				$status	&= Db::query($query, [], $dbName, false) !== false;
			}
			else if ($changed)
			{
				printf("    ➤ changing column \e[1;37m{$newColumn->name()}\e[0m\n");

				// Generate column query
				$query	= "alter table {$this->name} change {$newColumn->name()} {$newColumn->declaration()}";
				// Check if constraint changed on primary key with auto increment
				if ($oldColumn->property("primary") && $oldColumn->property("autoIncrement") && !$newColumn->property("primary"))
					$query .= ", drop primary key";
				$status &= Db::query($query, [], $dbName, false) !== false;
			}

			// Readd non composite constraints
			$constraints	= [];
			if ($foreign = $newColumn->property("foreign"))	// FOREIGN
				$constraints[] = "add constraint FK_{$this->name}_{$newColumn->name()} foreign key ({$newColumn->name()}) references {$foreign["table"]}({$foreign["column"]}) on delete {$foreign["onDelete"]} on update {$foreign["onUpdate"]}";
			if ($newColumn->property("primary") && !$newColumn->property("autoIncrement") && !($oldColumn->property("primary") && $oldColumn->property("autoIncrement")))	// PRIMARY
				$constraints[] = "add primary key ({$newColumn->name()})";
			if ($newColumn->property("unique"))				// UNIQUE
				$constraints[] = "add constraint UK_{$this->name}_{$newColumn->name()} unique key ({$newColumn->name()})";

			// Readd composite constraints
			if (($foreign = $newColumn->property("foreignComposite")) !== null)	// FOREIGN
			{
				$foreignChain[] = $newColumn;
				// Close chain
				if ($foreign["closeChain"] === true)
				{
					$compositeKey = [];
					$compositeRef = [];
					foreach ($foreignChain as $column)
					{
						$compositeKey[] = $column->name();
						$compositeRef[] = $column->property("foreignComposite")["column"];
					}

					$onDelete = $foreign["onDelete"];
					$onUpdate = $foreign["onUpdate"];
					$refTable = $foreign["table"];

					$constraints[] = "add constraint FK_{$this->name}_".implode("_", $compositeKey)." foreign key (".implode(", ", $compositeKey).") references $refTable(".implode(", ", $compositeRef).") on delete $onDelete on update $onUpdate";
					$foreignChain = [];
				}
			}
			if ($newColumn->property("primaryComposite") !== null)				// PRIMARY
			{
				$primaryChain[] = $newColumn;
				// Close chain
				if ($newColumn->property("primaryComposite") === true)
				{
					$compositeKey = array_map(function($column) {

						return $column->name();
					}, $primaryChain);

					$constraints[] = "add constraint PK_{$this->name}_".implode("_", $compositeKey)." primary key (".implode(", ", $compositeKey).")";
					$primaryChain = [];
				}
			}
			if ($newColumn->property("uniqueComposite") !== null)				// UNIQUE
			{
				$uniqueChain[] = $newColumn;
				// Close chain
				if ($newColumn->property("uniqueComposite") === true)
				{
					$compositeKey = array_map(function($column) {

						return $column->name();
					}, $uniqueChain);

					$constraints[] = "add constraint UK_{$this->name}_".implode("_", $compositeKey)." unique key (".implode(", ", $compositeKey).")";
					$uniqueChain = [];
				}
			}

			// Run query
			if (count($constraints))
				$status &= Db::query("alter table {$this->name} ".implode(", ", $constraints), [], $dbName, false) !== false;
		}

		// Dropped columns
		foreach ($oldColumns as $oldColumn)
		{
			$dropped = true;
			foreach ($newColumns as $newColumn)
				// If column exists it wasn't dropped
				if ($newColumn->name() === $oldColumn->name())
				{
					$dropped = false;
					break;
				}
			
			if ($dropped)
			{
				printf("    ➤ dropping column \e[1;37m{$oldColumn->name()}\e[0m\n");

				$query 	= "alter table {$this->name} drop column {$oldColumn->name()}";
				$status	&= Db::query($query, [], $dbName, false) !== false;
			}
		}

		// Return status
		return $status;
	}
}

/**
 * A column is a field of a table with a type and optional properties
 * 
 * @author sneppy
 */
class Column
{
	/**
	 * @var string $name column name
	 */
	protected $name;

	/**
	 * @var string $type column type
	 */
	protected $type;

	/**
	 * @var array $properties column properties
	 */
	protected $properties;

	/**
	 * Create a column
	 * 
	 * @param string	$name		column name
	 * @param string	$type		column type
	 * @param array		$properties	column properties
	 */
	public function __construct($name, $type, array $properties = [])
	{
		$this->name			= $name;
		$this->type			= $type;
		$this->properties	= $properties;
	}

	/**
	 * Get column name
	 * 
	 * @return string
	 */
	public function name()
	{
		return $this->name;
	}

	/**
	 * Get column type
	 * 
	 * @return string
	 */
	public function type()
	{
		$size = ($size = $this->property("size")) ? "(".$size.")" : "";
		return strtolower($this->type).$size;
	}

	/**
	 * Get column property(ies)
	 * 
	 * @param string $name if specified return property
	 * 
	 * @return array|string|null return null if property is not found
	 */
	public function property($name = null)
	{
		return !$name ? $this->properties : ($this->properties[$name] ?? null);
	}

	/**
	 * Set nullable or not nullable
	 * 
	 * @return Column
	 */
	public function notNullable()
	{
		return $this->set("notNullable");
	}

	/**
	 * Explicity set this column to be nullable
	 * 
	 * This is require in certain situations, for example with timestamp columns
	 * 
	 * @return Column
	 */
	public function nullable()
	{
		return $this->set("nullable");
	}

	/**
	 * Set data size
	 * 
	 * @param int $value column data size
	 * 
	 * @return Column
	 */
	public function size($value)
	{
		return $this->set("size", $value);
	}

	/**
	 * Set default
	 * 
	 * @param mixed $value default value for column
	 * 
	 * @return Column
	 */
	public function default($value)
	{
		return $this->set("default", $value);
	}

	/**
	 * Set unsigned
	 * 
	 * @return Column
	 */
	public function unsigned()
	{
		return $this->set("unsigned");
	}

	/**
	 * Set auto increment
	 * 
	 * @return Column
	 */
	public function autoIncrement()
	{
		return $this->set("autoIncrement");
	}

	/**
	 * Set on update property of timestamp
	 * 
	 * @param mixed $value value to set on table update
	 * 
	 * @return Column
	 */
	public function onUpdate($value = "current_timestamp")
	{
		return $this->set("onUpdate", $value);
	}

	/**
	 * Make unique key
	 * 
	 * @return Column
	 */
	public function unique()
	{
		return $this->set("unique");
	}

	/**
	 * Make composite unique key
	 * 
	 * @param bool $closeChain close composite chain
	 * 
	 * @return Column
	 */
	public function uniqueComposite($closeChain = false)
	{
		return $this->set("uniqueComposite", $closeChain);
	}

	/**
	 * Make primary key
	 * 
	 * @return Column
	 */
	public function primary()
	{
		return $this->set("primary");
	}

	/**
	 * Make composite primary key
	 * 
	 * @param bool $closeChain close composite chain
	 * 
	 * @return Column
	 */
	public function primaryComposite($closeChain = false)
	{
		return $this->set("primaryComposite", $closeChain);
	}

	/**
	 * Make foreign key
	 * 
	 * @param string		$model		model or name of foreign table
	 * @param string		$column		name of foreign column
	 * @param string|null	$onDelete	on delete option
	 * @param string|null	$onUpdate	on update option
	 * 
	 * @return Column
	 */
	public function references($model, $column = "id", $onDelete = "no action", $onUpdate = "no action")
	{
		$table = class_exists($model) ? $model::tableName() : $model;
		return $this->set("foreign", [
			"table"		=> $table,
			"column"	=> $column,
			"onDelete"	=> $onDelete ? strtolower($onDelete) : null,
			"onUpdate"	=> $onUpdate ? strtolower($onUpdate) : null
		]);
	}

	/**
	 * Make composite foreign key
	 * 
	 * @param string		$column		name of foreign column
	 * @param bool			$closeChain close composite chain
	 * @param string		$model		model or name of foreign table
	 * @param string|null	$onDelete	on delete option
	 * @param string|null	$onUpdate	on update option
	 * 
	 * @return Column
	 */
	public function referencesComposite($column, $closeChain = false, $model = "table", $onDelete = "no action", $onUpdate = "no action")
	{
		$table = class_exists($model) ? $model::tableName() : $model;
		return $this->set("foreignComposite", [
			"closeChain"	=> $closeChain,
			"table"			=> $table,
			"column"		=> $column,
			"onDelete"		=> $onDelete,
			"onUpdate"		=> $onUpdate
		]);
	}

	/**
	 * Return column declaration (no constraints)
	 * 
	 * @return string
	 */
	public function declaration()
	{
		$name = $this->name();
		$type = $this->type();

		$unsigned		= $this->property("unsigned");
		$notNullable	= $this->property("notNullable");
		$nullable		= $this->property("nullable");
		$default		= $this->property("default");
		$autoIncrement	= $this->property("autoIncrement");
		$properties		=
			($unsigned ? "unsigned " : "")						.
			($notNullable ? "not null " : "")					.
			($nullable ? "null " : "")							.
			($default !== null ? "default {$default} " : "")	.
			($autoIncrement ? "auto_increment " : "")			;

		return trim($name)." ".trim($type)." ".trim($properties);
	}

	/**
	 * Return constraints declarations
	 * 
	 * @param Table	$table	table of the column
	 * 
	 * @return array
	 */
	public function constraintsDeclarations(Table $table)
	{
		$name			= $this->name;
		$constraints	= [];
		if ($this->property("unique"))		// UNIQUE
			$constraints[] = "add constraint UK_{$table->name()}_{$name} unique key ($name)";
		if ($this->property("primary"))	// PRIMARY
			$constraints[] = "add constraint PK_{$table->name()}_{$name} primary key ($name)";
		if ($foreign = $this->property("foreign"))	// FOREIGN
			$constraints[] = "add constraint FK_{$table->name()}_{$name} foreign key ($name) references {$foreign["table"]}({$foreign["column"]}) on delete {$foreign["onDelete"]} on update {$foreign["onUpdate"]}";
		
		return $constraints;
	}

	/**
	 * Set column property
	 * 
	 * @param string	$name	name of the property
	 * @param mixed		$value	value of the property if necessary
	 * 
	 * @return Column
	 */
	protected function set($name, $value = true)
	{
		$this->properties[$name] = $value;
		return $this;
	}
}