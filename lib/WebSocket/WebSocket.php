<?php

namespace WebSocket;

/**
 * Socket interface
 * 
 * @author sneppy
 */
abstract class WebSocket
{
	/**
	 * @var resource $socket socket resource
	 */
	protected $socket;

	/**
	 * Create a new web socket interface
	 * 
	 * @param resource $socket socket resource
	 */
	public function __construct($socket)
	{
		$this->socket = $socket;
	}

	/**
	 * Get or set socket resource
	 * 
	 * @param resource $socket new socket resource
	 * 
	 * @return resource
	 */
	public function socket($socket = null)
	{
		if ($socket) $this->socket = $socket;
		return $this->socket;
	}

	/**
	 * Return info about this socket
	 * 
	 * @return array
	 */
	public function info() {}

	/**
	 * Return last error code
	 * 
	 * @return int
	 */
	public function lastErrorCode()
	{
		return socket_last_error($this->socket);
	}

	/**
	 * Return last error as string
	 * 
	 * @return string
	 */
	public function lastError()
	{
		return socket_strerror(socket_last_error($this->socket));
	}

	/**
	 * Wraps the data inside a websocket packet
	 * 
	 * @param string $d payload data
	 * 
	 * @return string
	 */
	protected static function wrap($d)
	{
		// FIN + rsv + opCode
		$b1 = 0x81;
		// Data length
		$l = strlen($d);

		// Pack header
		$h = $l < 0x7E ? pack("CC", $b1, $l) : (
			$l < 0x10000 ? pack("CCS", $b1, 0x7E, $l) : (
				pack("CCN", $b1, 0x7F, $l)
			)
		);

		// return packet
		return $h.$d;
	}

	/**
	 * Unmask websocket data
	 * 
	 * @param string $d data to unmask
	 * 
	 * @return string|bool return unmasked data or false if fails
	 */
	protected static function unmask($d)
	{
		if (!$d) return false;

		// Payload length field
		$l = ord($d[1]) & 0x7F;

		if ($l === 0x7E)
		{
			$m = substr($d, 4, 4);
			$d = substr($d, 8);
		}
		else if ($l === 0x7F)
		{
			$m = substr($d, 10, 4);
			$d = substr($d, 14);
		}
		else
		{
			$m = substr($d, 2, 4);
			$d = substr($d, 6);
		}

		// Unmask
		$text = "";
		for ($i = 0; $i < strlen($d); $i++)
		{
			$text .= $d[$i] ^ $m[$i % 4];
		}

		return $text;
	}
}
