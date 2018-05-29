<?php

namespace SnooPHP\Http;

/**
 * Handles redirection
 * 
 * @author sneppy
 */
class Redirect extends Response
{
	/**
	 * Create a new redirect response
	 * 
	 * @param string	$url	redirection URL
	 * @param int		$code	HTTP redirect code (>= 300)
	 */
	public function __construct($url, $code = 302)
	{
		// Check code for redirect
		// But allow anyway
		// Just throw a warning
		if ($code < 300 || $code >= 400) error_log("warning: invalid http status code ($code) for redirect");

		// Parent constructor with 'Location' header
		parent::__construct("", $code, [
			"Location" => $url
		]);
	}

	/**
	 * Parse response and redirect
	 */
	public function parse()
	{
		// Set response code
		http_response_code($this->code);

		// Set response header
		foreach ($this->headers as $header => $val)
		{
			header("$header: $val");
		}
	}

	/**
	 * Immediately redirect
	 * 
	 * @param string	$url	redirection URL
	 * @param int		$code	HTTP redirect code (>= 300)
	 */
	public static function now($url, $code = 302)
	{
		$redirect = new Redirect($url, $code);
		$redirect->parse();
		exit;
	}
}