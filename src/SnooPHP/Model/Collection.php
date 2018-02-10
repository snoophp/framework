<?php

namespace SnooPHP\Model;

/**
 * Wrapper for array of models
 * 
 * Mixed models are allowed, but some methods won't work properly
 * 
 * @author sneppy
 */
class Collection
{
	/**
	 * @var Model[] $models underlying array of models
	 */
	protected $models;

	/**
	 * Create a new collection from a set of models
	 * 
	 * @param Model[] $models set of models
	 */
	public function __construct(array $models)
	{
		$this->models = $models;
	}

	/**
	 * Return array of models
	 * 
	 * @return Model[]
	 */
	public function array()
	{
		return $this->models;
	}

	/**
	 * Return number of models
	 * 
	 * @return int
	 */
	public function size()
	{
		return count($this->models);
	}

	/**
	 * Return number of models
	 * @see size()
	 * 
	 * @return int
	 */
	public function num()
	{
		return $this->size();
	}

	/**
	 * Return true if empty
	 * 
	 * @return bool
	 */
	public function empty()
	{
		return count($this->models) === 0;
	}

	/**
	 * Return i-th element (if 0 <= i < size)
	 * 
	 * @return Model|null
	 */
	public function get($i = 0)
	{
		return count($this->models) > $i && $i >= 0 ? $this->models[$i] : null;
	}

	/**
	 * Return first element (if exists)
	 * 
	 * @return Model|null
	 */
	public function first()
	{
		return $this->get();
	}

	/**
	 * Return last element (if exists)
	 * 
	 * @return Model|null
	 */
	public function last()
	{
		return $this->get($this->size() - 1);
	}

	/**
	 * Find an element
	 * 
	 * @param callable|Model $criteria model to match or callable called on every element (return true if element match the criteria)
	 * 
	 * @return Model|int|null	if model is given, return index or null if not found.
	 * 							If callable given, return matching element or null if none found.
	 * 							In any case returns only a single result
	 */
	public function find($criteria)
	{
		if (!$criteria)
		{
			error_log("no criteria specified");
			return null;
		}

		else if (is_callable($criteria))
		{
			foreach ($this->models as $model)
			{
				if ($criteria($model)) return $model;
			}
		}
		else if (is_a($criteria, "SnooPHP\Model\Model"))
		{
			foreach ($this->models as $i => $model)
			{
				if ($model == $criteria) return $i;
			}
		}

		return null;
	}

	// MODIFIERS

	/**
	 * Run function on each model instance (can only modify instance property)
	 * 
	 * @param callable	$iterator	function called on every element of the collection.
	 * 								Return true to repeat on current instance, return false to break.
	 * 								Note that you should define the parameter as a reference in order to affect it.
	 * 
	 * @return Collection return this collection
	 */
	public function each(callable $iterator)
	{
		// No callable specified
		if (!$iterator)
		{
			error_log("no callable specified");
			return $this;
		}

		foreach ($this->models as $i => $model)
		{
			do $ctrl = call_user_func_array($iterator, array(&$models)); while ($ctrl === true);
			if ($ctrl === false) break;
		}

		return $this;
	}

	/**
	 * Append another collection
	 * 
	 * @param Collection $collection collection to append
	 * 
	 * @return Collection this collection
	 */
	public function append(Collection $collection)
	{
		if (!$collection) return $this;

		$this->models = array_merge($this->array(), $collection->array());
		return $this;
	}
}