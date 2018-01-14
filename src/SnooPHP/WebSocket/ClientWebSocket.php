<?php

namespace SnooPHP\WebSocket;

/**
 * Client-side websocket interface
 * 
 * @author sneppy
 */
class ClientWebSocket extends WebSocket
{
	/**
	 * @var string $ip public ip of client
	 */
	protected $ip;

	/**
	 * Create a new instance
	 * 
	 * @param resource $socket socket resource
	 */
	public function __construct($socket)
	{
		// Parent cosntructor
		parent::__construct($socket);
	}

	/**
	 * Send to this socket
	 * 
	 * @param string $message message to send to the socket
	 * 
	 * @return bool false if fails
	 */
	public function send($message)
	{
		if ($this->socket)
		{
			return socket_write($this->socket, static::wrap($message)) > 0 ? true : false;
		}

		return false;
	}

	/**
	 * Send as json string
	 * 
	 * @param object|array $json json string
	 * 
	 * @return bool false if fails
	 */
	public function sendJson($json)
	{
		return $this->send(json_encode($json));
	}

	/**
	 * Read from this socket
	 * 
	 * @return string|bool|int message, 0 if disconnected and false on error
	 */
	public function read($maxLength = 1024)
	{
		if ($this->socket)
		{
			$status = socket_recv($this->socket, $message, $maxLength, MSG_DONTWAIT);
			return $status === 0 ? 0 : static::unmask($message);
		}

		return false;
	}

	/**
	 * Return info about this socket
	 * 
	 * @return array
	 */
	public function info()
	{
		return [
			"ip" => $this->ip
		];
	}

	/**
	 * Try to read the header of the HTTP request
	 * 
	 * This happens when the client wants to establish the connection
	 * 
	 * @return array|false set of headers or false if fails
	 */
	public function httpHeader()
	{
		$H = [];
		// Read from socket
		$h = socket_read($this->socket, 1024);
		if ($h)
		{
			$L = preg_split("/\r\n/", $h);
			if (!$L || !count($L)) return false;

			foreach ($L as $l)
			{
				$l = chop($l);
				if (preg_match("/\A(\S+): (\S+)/", $l, $m)) $H[$m[1]] = $m[2];
			}

			if (count($H) > 0)
			{
				// Set ip of client
				$this->ip = $H["X-Forwarded-For"] ?: "N/A";

				return $H;
			}
		}
		
		return false;
	}

	/**
	 * Perform handshake
	 * 
	 * @param string $acceptKey the generated accept key
	 */
	public function handshake($acceptKey)
	{
		$r =	"HTTP/1.1 101 Switching Protocol\r\n" .
				"Upgrade: websocket\r\n" .
				"Connection: Upgrade\r\n" .
				"Sec-WebSocket-Accept: ".$acceptKey."\r\n\r\n";
		socket_write($this->socket, $r, strlen($r));
	}
}
