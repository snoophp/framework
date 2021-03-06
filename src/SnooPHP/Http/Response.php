<?php

namespace SnooPHP\Http;

/**
 * Response to send to the client
 * 
 * @author sneppy
 */
class Response
{
	/**
	 * @var bool $ignoreDefaultHeaders if true default headers won't be appended to response
	 */
	public $ignoreDefaultHeaders = false;

	/**
	 * @var string $content Response content (can be null)
	 */
	protected $content;
	
	/**
	 * @var int $code http response code
	 */
	protected $code;

	/**
	 * @var array $headers list of headers
	 */
	protected $headers;

	/**
	 * Create a new response
	 * 
	 * @param string	$content	body of the response
	 * @param int		$code		HTTP code of the response
	 * @param array		$headers	set of additional headers
	 */
	public function __construct($content = "", $code = 200, array $headers = [])
	{
		$this->content	= $content;
		$this->code		= $code;
		$this->headers	= $headers;
	}

	/**
	 * Apply response
	 */
	public function parse()
	{
		// Set response code
		http_response_code($this->code);

		// Set response header
		foreach ($this->headers as $header => $val)
			header("$header: $val");

		// Echo content
		echo $this->content;
	}

	/**
	 * Get or set content
	 * 
	 * @param string|null $content content to set or null to retrieve only
	 * 
	 * @return string
	 */
	public function content($content = null)
	{
		if ($content) $this->content = $content;
		return $this->content;
	}

	/**
	 * Get or set http code
	 * 
	 * @param int|null $code http code to set or null to get only
	 * 
	 * @return int
	 */
	public function code($code = null)
	{
		if ($code) $this->code = $code;
		return $this->code;
	}

	/**
	 * Get or set header
	 * 
	 * @deprecated v0.2.5
	 * 
	 * If both parameters are null all headers are returned
	 * otherwise the header identified by $name field is returned
	 * and if $value is not null the field value is updated
	 * 
	 * @param string|array	$name	field name
	 * @param string|array	$value	header value or array of field => value to set
	 * 
	 * @return string|array
	 */
	public function header_old($name = null, $value = null)
	{
		// Set
		if ($value !== null)
		{
			if (is_array($value))
				foreach ($value as $field => $val) $this->headers[$field] = $val;
			else if (is_string($name))
				$this->headers[$name] = $value;
		}

		// Get
		return !$name ? $this->headers : ($this->headers[$name] ?? null);
	}

	/**
	 * Get or set header
	 * 
	 * If $header is a string, the method returns the associated value. in any other case, the full array of headers is returned
	 * if $header is an array, the list of headers is appended to the current one
	 * 
	 * @param string|null|array $header name of header to retrieve, null or list of headers to append
	 * 
	 * @return string|array
	 */
	public function header($header = [])
	{
		if (is_array($header))
			$this->headers = array_merge($this->headers, $header);
		
		return is_string($header) ? ($this->headers[$header] ?? null) : $this->headers;
	}

	/**
	 * Return view (parse php)
	 * 
	 * @param string	$name		view name
	 * @param array		$args		list of arguments available to the view
	 * @param Request	$request	specify if differs from current request
	 * 
	 * @return Response
	 */
	public static function view($name, array $args = [], Request $request = null)
	{
		// Get request
		$request = $request ?: Request::current();

		// Capture output buffer
		ob_start();
		include path("views/$name.php", true);
		$content = ob_get_contents();
		ob_end_clean();

		// Return response
		return new static($content);
	}

	/**
	 * Return json content
	 * 
	 * @param string|mixed	$content	data to be converted to json
	 * @param int			$code		http status code (default: 200)
	 * 
	 * @return Response
	 */
	public static function json($content, $code = 200)
	{
		// If collection get array
		if (is_a($content, "SnooPHP\Model\Collection")) $content = $content->array();

		// Return json content
		return new static(
			to_json($content),
			$code,
			["Content-Type" => "application/json; charset=utf-8"]
		);
	}

	/**
	 * Return a resource (file)
	 * 
	 * Resource are stored in the `storage` directory
	 * 
	 * @param string		$file			path to the resource, relativo to the storage directory
	 * @param string|null	$type			MIME type of the resource (default: null)
	 * @param bool			$evaluatePhp	if true php code in the resource will be evaluated before outputting the content (default: false)
	 * 
	 * @return Response
	 */
	public static function resource($file, $type = null, $evaluatePhp = false)
	{
		if ($path = path("resources/$file", true))
		{
			ob_start();
			$evaluatePhp ? include($path) : readfile($path);
			$content = ob_get_contents();
			ob_end_clean();

			return new static($content, 200, ["Content-Type" => $type ?: mime_type($path)]);
		}

		static::abort(404, [
			"status"		=> "ERROR",
			"description"	=> "resource not found"
		]);
	}

	/**
	 * Generate an error response
	 * 
	 * @param int					$code		http response code
	 * @param string|array|object	$content	optional content
	 */
	public static function abort($code, $content = "")
	{
		throw new AbortRouteException($content, $code);
	}
}