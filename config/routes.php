<?php

use Http\Router;
use Http\Request;
use Http\Response;

/********************
 * APPLICATION ROUTES
 ********************/

/**
 * @var Router $router application router
 */
$router = new Router();

/* Home page */
$router->get("/", function($request) {

	return Response::json([
		"message"	=> "Hello World!"
	]);
});