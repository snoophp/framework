<?php

namespace SnooPHP\Http;

/**
 * Route manager
 * 
 * When called, analyzes the incoming request
 * and execute first matching route, if any
 * 
 * @author sneppy
 */
class Router
{
	/**
	 * @var string $base base path to prepend
	 */
	protected $base;
	
	/**
	 * @var Route[] $routes list of configured routes
	 */
	protected $routes = [];

	/**
	 * @var Callable $errorAction action executed on 404
	 */
	protected $errorAction = null;

	/**
	 * Create a new router
	 * 
	 * Note that base only affects routes creation, not routes matching
	 * 
	 * @param string $base base path
	 */
	public function __construct($base = "")
	{
		$this->base = $base;
	}

	/**
	 * Get or set router base
	 * 
	 * @param string|null $base new base for router
	 * 
	 * @return String
	 */
	public function base($base = null)
	{
		return $this->base;
	}

	/**
	 * Parse request
	 * 
	 * @param Request $request request to handle
	 * 
	 * @return Response|null|bool
	 */
	public function handle(Request $request)
	{
		return $this->match($request);
	}

	/**
	 * Add a GET route
	 * 
	 * @param string	$url	route url
	 * @param Callable	$action	action to perform on match
	 * 
	 * @return Route
	 */
	public function get($url, Callable $action = null)
	{
		return $this->add($url, "GET", $action);
	}

	/**
	 * Add a POST route
	 * 
	 * @param string	$url	route url
	 * @param Callable	$action	action to perform on match
	 * 
	 * @return Route
	 */
	public function post($url, Callable $action = null)
	{
		return $this->add($url, "POST", $action);
	}

	/**
	 * Add a PUT route
	 * 
	 * @param string	$url	route url
	 * @param Callable	$action	action to perform on match
	 * 
	 * @return Route
	 */
	public function put($url, Callable $action = null)
	{
		return $this->add($url, "PUT", $action);
	}

	/**
	 * Add a DELETE route
	 * 
	 * @param string	$url	route url
	 * @param Callable	$action	action to perform on match
	 * 
	 * @return Route
	 */
	public function delete($url, Callable $action = null)
	{
		return $this->add($url, "DELETE", $action);
	}

	/**
	 * Get or set error action
	 * 
	 * @param Callable|null $action action to be executed
	 * 
	 * @return Callable
	 */
	public function error(Callable $action = null)
	{
		if ($action) $this->errorAction = $action;
		return $this->errorAction;
	}

	/**
	 * Alias for error action
	 * 
	 * @deprecated v2.2
	 * @see Router::error
	 */
	public function errorAction(Callable $action = null)
	{
		return $this->error($action);
	}

	/**
	 * Return response of first route that matches, false otherwise
	 * 
	 * If a route action returns false, following routes have a chance at matching
	 * After the first match the function returns. In case of possible multiple matches
	 * the route declared first is taken as valid (too expensive to check match length).
	 * Pay attention to "greedy" routes (with + or *) can shadow other routes.
	 * 
	 * @param Request $request request to test
	 * 
	 * @return Response|bool
	 */
	protected function match(Request $request)
	{
		foreach ($this->routes as $route)
		{
			if ($route->method() === $request->method() && $route->match($request->url()))
			{
				if ($action = $route->action())
				{
					$response = $action($request, $route->args());
					if ($response !== false) return $response;
				}
				else
					// Match found but no action to perform
					return null;
			}
		}

		// No match found
		return false;
	}

	/**
	 * Add a generic route
	 * 
	 * Use dedicated methods:
	 * @see Router::get
	 * @see Router::post
	 * @see Router::put
	 * @see Router::delete
	 * 
	 * @param string	$url	route url
	 * @param string	$method	route method
	 * @param Callable	$action	route action
	 * 
	 * @return Route
	 */
	protected function add($url, $method, Callable $action)
	{
		$url = $this->base !== "" && $url === "/" ? $this->base : $this->base.$url;
		$route = new Route($url, $method, $action);
		$this->routes[] = $route;

		return $route;
	}
}