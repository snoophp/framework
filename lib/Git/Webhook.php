<?php

namespace Git;

use Http\Request;
use Http\Response;

/**
 * A generic webhook
 * 
 * @author sneppy
 */
abstract class Webhook
{
	/**
	 * Handle a webhook request
	 * 
	 * @param Request $request webhook request
	 * 
	 * @return Response
	 */
	abstract public static function handle(Request $request);
}