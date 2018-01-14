<?php

namespace SnooPHP\Http;

/**
 * Http request object
 * 
 * @author sneppy
 */
class Request
{
	/**
	 * @var string $url request url
	 */
	protected $url;

	/**
	 * @var string $method request method
	 */
	protected $method;

	/**
	 * @var string $time request timestamp
	 */
	protected $time;

	/**
	 * @var array $headers request headers
	 */
	protected $headers = [];

	/**
	 * @var array $input request input
	 */
	protected $inputs = [];

	/**
	 * @var array $files request input files
	 */
	protected $files = [];

	/**
	 * Create a new request
	 * 
	 * @param string	$url		requested url
	 * @param string	$method		request HTTP method
	 * @param string	$time		request timestamp as string
	 * @param array		$headers	request headers
	 * @param array		$inputs		input of the request (GET, POST data)
	 * @param array		$files		files sent with the request
	 */
	public function __construct($url, $method = "GET", $time = null, array $headers = [], array $inputs = [], array $files = [])
	{
		$this->url		= $url;
		$this->method	= $method;
		$this->time		= $time ?: date();
		$this->headers	= $headers;
		$this->inputs	= $inputs;
		$this->files	= $files;
	}

	/**
	 * Return request url
	 * 
	 * @return string
	 */
	public function url()
	{
		return $this->url;
	}

	/**
	 * Return request method
	 * 
	 * @return string
	 */
	public function method()
	{
		return $this->method;
	}

	/**
	 * Return request timestamp
	 * 
	 * @param bool $timestamp if true return as timestamp
	 * 
	 * @return string|int
	 */
	public function time($timestamp = false)
	{
		return $timestamp ? (new DateTime($this->time))->getTimestamp() : $this->time;
	}

	/**
	 * Return all request headers or a specific one
	 * 
	 * @param string $name field name
	 * 
	 * @return string|array|null
	 */
	public function header($name = null)
	{
		return !$name ? $this->headers : (
			isset($this->headers[$name]) ? $this->headers[$name] : (
				null
			)
		);
	}

	/**
	 * Return request input
	 * 
	 * @param string	$name		input name or null to return whole inputs array
	 * @param mixed		$default	default value returned if such input is found
	 * 
	 * @return array|mixed|null
	 */
	public function input($name = null, $default = null)
	{
		return !$name ? $this->inputs : (
			isset($this->inputs[$name]) ? $this->inputs[$name] : (
				$default
			)
		);
	}

	/**
	 * Return request input files
	 * 
	 * @param string	$name	file name or null to return whole inputs array
	 * 
	 * @return array|mixed
	 */
	public function file($name = null)
	{
		return !$name ? $this->files : (
			isset($this->files[$name]) ? $this->files[$name] : (
				null
			)
		);
	}

	/**
	 * Return true if input is valid
	 * 
	 * @param array $rules list of input rules
	 * 
	 * @return bool
	 */
	public function validateInput($rules)
	{
		foreach ($rules as $rule)
		{
			if (empty($this->inputs[$rule])) return false;
		}

		return true;
	}

	/**
	 * Return current request
	 * 
	 * @return Request
	 */
	public static function current()
	{
		$url		= $_SERVER["REQUEST_URI"];
		$method		= $_SERVER["REQUEST_METHOD"];
		$time		= $_SERVER["REQUEST_TIME_FLOAT"];
		$headers	= getallheaders() ?: [];
		$inputs		= [];
		$files		= [];

		// Populate input
		$raw = [];
		switch ($method)
		{
			case "GET":
				$raw = $_GET;
				break;
			case "POST":
				$raw = $_POST;
				break;
			default:
				parse_str(file_get_contents("php://input"), $raw);
		}
		foreach ($raw as $input => $val) $inputs[$input] = \Utils::parseValue($val);

		// Populate files
		foreach ($_FILES as $name => $file) $files[$name] = $file;

		// Return request
		return new Request(
			$url,
			$method,
			$time,
			$headers,
			$inputs,
			$files
		);
	}
}
