<?php

namespace Http;

/**
 * Incoming request wrapper
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
	 * Create a new request
	 * 
	 * @param string	$url		requested url
	 * @param string	$method		request HTTP method
	 * @param string	$time		request timestamp as string
	 * @param array		$headers	set of additional headers
	 */
	public function __construct($url, $method = "GET", $time = null, array $headers = [])
	{
		$this->url = $url;
		$this->method = $method;
		$this->time = $time ?: date();
		$this->headers = $headers;

		// Set data
		$raw = [];
		switch ($this->method)
		{
			case "GET":
				$raw = $_GET;
				break;

			case "POST":
				$raw = $_POST;
				break;

			default:
				parse_str(file_get_contents("php://input"), $raw);
				break;
		}
		foreach ($raw as $input => $val) $this->inputs[$input] = \Utils::parseValue($val);
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
		return new Request(
			$_SERVER["REQUEST_URI"],
			$_SERVER["REQUEST_METHOD"],
			$_SERVER["REQUEST_TIME_FLOAT"],
			getallheaders() ?: []
		);
	}
}
