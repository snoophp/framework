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
$router = register_router(new Router());

/* Error page */
$router->errorAction(function($request) {

	Response::abort(404);
});

/* Home page */
$router->get("/", function($request) {

	return Response::json([
		"message"	=> "Hello World!"
	]);
});