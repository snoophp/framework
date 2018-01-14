<?php

namespace SnooPHP\Http\Curl;

/**
 * Provides a friendly interface to perform HTTP requests using cURL library
 * 
 * @author sneppy
 */
abstract class Curl
{
	/**
	 * @var resource $curl underlying curl resource
	 */
	protected $curl;

	/**
	 * @var string $url session url
	 */
	protected $url;

	/**
	 * @var string $lastResult result of last execution
	 */
	protected $lastResult = null;

	/**
	 * @var string $lastResultType response Content-Type value
	 */
	protected $lastResultType = "text/plain";

	/**
	 * @var array $lastResultHeader set of headers from the last result
	 */
	protected $lastHeader = [];

	/**
	 * @var array $info array with information about the session
	 */
	protected $info = null;

	/**
	 * Create a new cURL session
	 * 
	 * @param string	$url		session url
	 * @param array		$options	set of options
	 * @param array		$headers	list of http headers
	 * @param bool		$initOnly	if true the session won't be executed
	 */
	public function __construct($url = null, array $options = [], array $headers = [], $initOnly = true)
	{
		// Init curl session
		$this->curl = curl_init($this->url = $url);

		// Set options
		$this->option($options);
		$this->option([CURLOPT_HEADERFUNCTION => [&$this, "parseHeader"]]);

		// Set headers
		$headerList = [];
		foreach ($headers as $header => $val) $headerList[] = $header.": ".$val;
		$this->option([CURLOPT_HTTPHEADER => $headerList]);

		// Execute session
		if (!$initOnly) $this->exec();
	}

	/**
	 * Execute and close session
	 * 
	 * @param bool $keepAlive if true the session will not be closed
	 * 
	 * @return bool false if execution failed, true otherwise
	 */
	public function exec($keepAlive = false)
	{
		$this->lastResult	= curl_exec($this->curl);
		$this->info			= curl_getinfo($this->curl);

		// Close session
		if (!$keepAlive) curl_close($this->curl);

		return $this->lastResult !== false;
	}

	/**
	 * Get curl response body
	 * 
	 * @param bool $decodeJson if true and return type is json decode content
	 * 
	 * @return null|bool|string
	 */
	public function content($decodeJson = false)
	{
		return $decodeJson && $this->lastResultType === "application/json" && $this->lastResult ?
		\SnooPHP\Utils::toJson($this->lastResult) :
		$this->lastResult;
	}

	/**
	 * Get curl reponse content type
	 * 
	 * @return string
	 */
	public function contentType()
	{
		return $this->lastResultType;
	}

	/**
	 * Get curl response code
	 * 
	 * @return int
	 */
	public function code()
	{
		return $this->info ?
		$this->info["http_code"] :
		curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
	}

	/**
	 * Return true if response is success
	 * 
	 * @return bool
	 */
	public function success()
	{
		return $this->code() < 400;
	}

	/**
	 * Get session info
	 * 
	 * @param string $info if set return associated information
	 * 
	 * @return array|string
	 */
	public function info($name = null)
	{
		if ($name)
		{
			return $this->info ?
			$this->info[$name] :
			curl_getinfo($this->curl, "CURLINFO".strtoupper($name));
		}
		else
		{
			return $this->info ?: curl_getinfo($this->curl);
		}
	}

	/**
	 * Set or get session url
	 * 
	 * @param string|null $url if set update session url
	 * 
	 * @return string
	 */
	public function url($url = null)
	{
		if ($url)
		{
			$this->url = $url;
			$this->option([CURLOPT_URL => $url]);
		}
		
		return $this->url;
	}

	/**
	 * Set option
	 * 
	 * @param array $options associative array
	 */
	public function option(array $options = [])
	{
		curl_setopt_array($this->curl, $options);
	}

	/**
	 * Return last error as string
	 * 
	 * @return string
	 */
	public function error()
	{
		return curl_error($this->curl);
	}

	/**
	 * Return last error code
	 * 
	 * @return int|null
	 */
	public function errorCode()
	{
		return curl_errno($this->curl);
	}

	/**
	 * Parse header line
	 * 
	 * @param resource	$curl	curl resource
	 * @param string	$header	header line
	 * 
	 * @return int
	 */
	protected function parseHeader($curl, $header)
	{
		if (preg_match("/^([^:\s]+)\:\s+(.*)$/", $header, $matches))
		{
			// Add to header list
			$matches[2] = trim($matches[2]);
			$this->lastHeader[$matches[1]] = $matches[2];

			// Set result type
			$this->lastResultType = $matches[1] === "Content-Type" ? $matches[2] : $this->lastResultType;			
		}

		return strlen($header);
	}
}