<?php

namespace SnooPHP\WebSocket;

/**
 * Server-side websocket interface
 * 
 * @author sneppy
 */
class ServerWebSocket extends WebSocket
{
	/**
	 * @var string|int $host host to bind to
	 */
	protected $host = 0;

	/**
	 * @var int $port port to bind to
	 */
	protected $port = 8080;

	/**
	 * Create a new server websocket
	 * 
	 * By default bind to host := 0 and port := 8080 as non-blocking
	 * 
	 * @param resource		$socket	server socket
	 * @param array|null 	$opt	optional socket parameters
	 */
	public function __construct($socket = null, $opt = null)
	{
		// Parent constructor
		parent::__construct($socket);

		// Create socket if not defined
		if (!$this->socket) $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		// Default options
		socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_set_nonblock($this->socket);

		// Custom options
		if ($this->socket && $opt)
		{
			$opt = (object) $opt;

			// Host and port
			if (!empty($opt->host)) $this->host = $opt->host;
			if (!empty($opt->port)) $this->port = $opt->port;

			// Blocking
			if (isset($opt->block) && $opt->block) socket_set_block($this->socket);
		}

		// Bind socket
		socket_bind($this->socket, $this->host, $this->port);
	}

	/**
	 * Start listening
	 * 
	 * @return bool false if fails
	 */
	public function listen()
	{
		if ($this->socket)
		{
			socket_listen($this->socket);
			return true;
		}
		
		return false;
	}

	/**
	 * Accept client and perform handshake
	 * 
	 * @param ClientWebSocket	$incoming	incoming client socket
	 * @param string|null		$message	a message to send after handshake
	 * 
	 * @return bool
	 */
	public function welcome($incoming, $message = null)
	{
		if ($header = $incoming->httpHeader())
		{
			$key = $header["Sec-WebSocket-Key"];
			if (!$key) return false;

			// Perform handshake
			$acceptKey = static::generateAcceptKey($key);
			$incoming->handshake($acceptKey);

			if ($message) $incoming->send($message);

			return true;
		}

		return false;
	}

	/**
	 * Accept an incoming socket
	 * 
	 * @return ClientWebSocket|bool return connecting client scoket or false if fails
	 */
	public function accept()
	{
		$incoming = socket_accept($this->socket);
		return $incoming ? new ClientWebSocket($incoming) : false;
	}

	/**
	 * Return info about this socket
	 * 
	 * @return array
	 */
	public function info()
	{
		return [
			"host" => $this->host,
			"port" => $this->port
		];
	}

	/**
	 * Generate websocket accept key
	 * 
	 * @param string $key base_64 websocket request key
	 * 
	 * @return string
	 */
	protected static function generateAcceptKey($key)
	{
		return base64_encode(pack("H*", sha1($key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11")));
	}
}
