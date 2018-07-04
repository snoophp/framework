<?php

if (!function_exists('getallheaders'))
{
	/**
	 * Backup function for getallheaders
	 * 
	 * Turns out some PHP environment doesn't have this function
	 * This is a surrogate that performs exactly the same task
	 * 
	 * @return array
	 */
	function getallheaders()
	{
		$headers = [];
		foreach ($_SERVER as $name => $val)
			if (substr($name, 0, 5) === "HTTP_")
				$headers[str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($name, 5)))))] = $val;
		
		// Return headers
		return $headers;
    }
}

if (!function_exists("validate_ip"))
{
	/**
	 * Check ip address against a test ip
	 * 
	 * Ips should be in x.y.z.x/w form, where w is the mask
	 * 
	 * @param string	$ip		ip address to check
	 * @param string	$test	ip address used as test
	 * 
	 * @return bool
	 */
	function validate_ip($ip, $test)
	{
		if (!is_string($ip) || !is_string($test)) return false;
		$testBytes = preg_split("@(?:\.|/)@", $test);
		$ipBytes = preg_split("@(?:\.|/)@", $ip);
		$test = unpack("N", pack("C*", $testBytes[0], $testBytes[1], $testBytes[2], $testBytes[3]))[1];
		$ip = unpack("N", pack("C*", $ipBytes[0], $ipBytes[1], $ipBytes[2], $ipBytes[3]))[1];
		if (isset($testBytes[4]) && $mask = (int)$testBytes[4])
		{
			$test = $test >> (32 - $mask);
			$ip = $ip >> (32 - $mask);
		}

		return $test === $ip;
	}
}