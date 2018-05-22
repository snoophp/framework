<?php

namespace SnooPHP\Curl;

/**
 * POST request wrapper
 * 
 * @author sneppy
 */
class Post extends Curl
{
	/**
	 * Create a new POST cURL session
	 * 
	 * @param string		$url		session url
	 * @param string|array	$post		post data as an associative array or url encoded string
	 * @param array			$headers	list of http headers
	 */
	public function __construct($url, $post = "", array $headers = [])
	{
		if (is_string($post)) $headers["Content-Type"] = "application/x-www-form-urlencoded";
		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "POST",
			CURLOPT_POSTFIELDS		=> $post,
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, false);
	}

	/**
	 * Create a new session with an authorization header
	 * 
	 * @param string		$url		request url
	 * @param string		$authKey	authorization key
	 * @param string|array	$post		post data as an associative array or urlencoded string
	 * 
	 * @return Post
	 */
	public static function withAuth($url, $authKey = "", $post = "")
	{
		return new Post($url, $post, ["Authorization" => $authKey]);
	}
}