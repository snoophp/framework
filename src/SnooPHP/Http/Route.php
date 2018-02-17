<?php

namespace SnooPHP\Http;

/**
 * A Route for the application
 * 
 * Defines an HTTP route identified by method and url
 * with an optional action and name
 * 
 * @author sneppy
 */
class Route
{
	/**
	 * @var string $url route url
	 */
	protected $url;

	/**
	 * @var string $method route method
	 */
	protected $method;

	/**
	 * @var Callable $action action to execute on match
	 */
	protected $action;

	/**
	 * @var string $name route name
	 */
	protected $name;

	/**
	 * @var array $args route arguments
	 */
	protected $args;

	/**
	 * Create a new route
	 * 
	 * @param string	$url	route url
	 * @param string	$method	route method
	 * @param Callable	$action	route action
	 * @param string	$name	route name
	 */
	public function __construct($url = "/", $method = "GET", Callable $action = null, $name = null)
	{
		$this->url		= trim($url);
		$this->method	= trim($method);
		$this->action	= $action;
		$this->name		= $name ?: $this->url;
	}

	/**
	 * Get or set route url
	 * 
	 * @param string $url if not null updated url property
	 * 
	 * @return string
	 */
	public function url($url = null)
	{
		if ($url) $this->url = trim($url);
		return $this->url;
	}

	/**
	 * Get or set route method
	 * 
	 * @param string $method if not null update method
	 * 
	 * @return string
	 */
	public function method($method = null)
	{
		if ($method) $this->method = trim($method);
		return $this->method;
	}

	/**
	 * Get or set route action
	 * 
	 * @param Callable $action if specified replace current action
	 * 
	 * @return Callable
	 */
	public function action(Callable $action = null)
	{
		if ($action) $this->action = $action;
		return $this->action;
	}

	/**
	 * Get or set route name
	 * 
	 * @param string $name if specified replace current route name
	 * 
	 * @return string
	 */
	public function name($name = null)
	{
		if ($name) $this->name = $name;
		return $this->name;
	}

	/**
	 * Get route arguments
	 * 
	 * @return array
	 */
	public function args()
	{
		return $this->args;
	}

	/**
	 * Determine if test string matches this route
	 * 
	 * @param string $test test url string
	 * 
	 * @return array
	 */
	public function match($test)
	{
		// Get argument names
		preg_match_all("/{([^\s}]*)}/", $this->url, $args);
		// Get values
		$pattern = preg_replace("/{[^\s}]*}/", "([^\s/?]+)", $this->url);
		if (preg_match("@^".$pattern."(?:/)?(?:\?.*)?$@", $test, $vals) > 0)
		{
			// Fill input array
			$input = [];
			foreach ($args[1] as $i => $arg) $this->args[$arg] = $vals[$i + 1];

			return true;
		}
		
		return false;
	}
}