<?php

namespace Http;

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
		// Create and run redirect
		parent::__construct("", $code, [
			"Location" => $url
		]);
	}

	/**
	 * Redirect
	 */
	public function parse()
	{
		// Set response code
		http_response_code($this->code);

		// Set response header
		foreach ($this->headers as $header => $val)
		{
			header($header.": ".$val);
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