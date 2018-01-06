<?php

namespace Http;

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
	 * @var Route $routes list of configured routes
	 */
	protected $routes = [];

	/**
	 * @var Callable $errorAction action executed on 404
	 */
	protected $errorAction;

	/**
	 * Parse request
	 * 
	 * @param Request $request request to handle
	 * 
	 * @return Response|null
	 */
	public function handle(Request $request)
	{
		$res = $this->match($request);
		return $res !== false ? $res : $this->errorAction();
	}

	/**
	 * Add a GET route
	 * 
	 * @param string	$url	route url
	 * @param Callable	$action	action to perform on match
	 * 
	 * @return Route
	 */
	public function get($url, $action = null)
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
	public function post($url, $action = null)
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
	public function put($url, $action = null)
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
	public function delete($url, $action = null)
	{
		return $this->add($url, "DELETE", $action);
	}

	/**
	 * Set error action
	 * 
	 * @param Callable $action action to be executed
	 */
	public function errorAction($action)
	{
		$this->errorAction = $action;
	}

	/**
	 * Return response of first route that matches, false otherwise
	 * 
	 * If a route action returns false, following routes have a chance at matching
	 * 
	 * @param Request $request request to test
	 * 
	 * @return Response|bool
	 */
	protected function match($request)
	{
		foreach ($this->routes as $route)
		{
			if ($route->method() === $request->method() && $route->match($request->url()))
			{
				if ($res = $route->action()($request, $route->args())) return $res;
			}
		}

		return false;
	}

	/**
	 * Add a generic route
	 * 
	 * @param string	$url	route url
	 * @param string	$method	route method
	 * @param Callable	$action	route action
	 * 
	 * @return Route
	 */
	protected function add($url, $method, $action)
	{
		$route = new Route($url, $method, $action);
		$this->routes[] = $route;

		return $route;
	}
}