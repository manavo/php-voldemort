<?php

namespace Voldemort;

class Node {

	private $host;
	private $socketPort;

	function __construct($host, $socketPort)
	{
		$this->host = $host;
		$this->socketPort = $socketPort;
	}

	public static function fromXml($serverXml) {
		$host = (string)$serverXml->host;
		$socketPort = (string)$serverXml->{'socket-port'};

		return new static($host, $socketPort);
	}

	/**
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * @return string
	 */
	public function getSocketPort()
	{
		return $this->socketPort;
	}

}
