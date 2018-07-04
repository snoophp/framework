<?php

namespace SnooPHP\Curl;

/**
 * DELETE request wrapper
 * 
 * @author sneppy
 */
class Delete extends Curl
{
	/**
	 * Create a new DELETE request
	 * 
	 * @param string	$url		requested url
	 * @param array		$headers	list of http headers
	 * @param bool		$initOnly	if true the session won't be executed
	 */
	public function __construct($url, array $headers = [], $initOnly = false)
	{
		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "DELETE",
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, $initOnly);
	}
}