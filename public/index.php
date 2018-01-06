<?php

require __DIR__."/../autoloader.php";

use Http\Request;

/***************
 * Parse request
 ***************/
if ($router && $request = Request::current())
{
	if ($response = $router->handle($request))
	{
		$response->parse();
	}
}

// Flush errors
\Utils::flushErrors();

exit;