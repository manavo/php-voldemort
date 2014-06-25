<?php

namespace Voldemort;

class Node {

	private $id;
	private $host;
	private $socketPort;

	function __construct($id, $host, $socketPort)
	{
		$this->id = $id;
		$this->host = $host;
		$this->socketPort = $socketPort;
	}

	public static function fromXml($serverXml) {
		$id = (int)$serverXml->id;
		$host = (string)$serverXml->host;
		$socketPort = (string)$serverXml->{'socket-port'};

		return new static($id, $host, $socketPort);
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
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
