<?php

require __DIR__."/../autoloader.php";

use Http\Router;
use Http\Request;
use Http\Response;

/***************
 * Parse request
 ***************/
if ($request = Request::current())
{
	$notFound = true;
	foreach ($routers as $router)
	{
		$response = $router->handle($request);
		if ($response !== false)
		{
			$notFound = false;
			if ($response) $response->parse();
			break;
		}
	}

	// Get error action
	if ($notFound)
	{
		$match = null;
		foreach($routers as $router)
		{
			$base		= rtrim($router->base(), "\/");
			$pattern	= "@^".$base."(?:/[^/]*)*$@";

			if (empty($base) && $match == null && $router->errorAction() !== null)
			{
				$match = $router;
			}
			else if (preg_match($pattern, $request->url()) && $router->errorAction() !== null)
			{
				$match = $router;
				break;
			}
		}

		if ($match)
		{
			$match->errorAction()()->parse();
		}
		else
		{
			Response::abort(404);
		}
	}
}

// Flush errors
\Utils::flushErrors();

exit;