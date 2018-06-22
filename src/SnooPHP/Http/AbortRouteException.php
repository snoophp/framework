<?php

namespace SnooPHP\Http;

use Throwable;
use Exception;

/**
 * An exception thrown when a route is aborted
 * 
 * @author Sneppy
 */
class AbortRouteException extends Exception
{
	/**
	 * @var bool isJson true if message is a json string
	 */
	protected $isJson = false;

	/**
	 * Builds on top of default constructor
	 * 
	 * @param object|string|array	$message	abort message. Objects and arrays are returned as json objects
	 * @param int					$code		must be a valid http status code
	 * @param Exception|null		$previous	previous exception
	 */
	public function __construct($message = "server error", $code = 500, Throwable $previous = null)
	{
		if (is_object($message) || is_array($message))
		{
			$this->isJson = true;
			$message = to_json($message);
		}

		// Parent constructor
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Create response from exception
	 * 
	 * @return Response
	 */
	public function response()
	{
		return new Response($this->getMessage(), $this->getCode(), $this->isJson ? ["Content-Type" => "application/json; charset=utf-8"] : []);
	}
}