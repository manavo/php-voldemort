<?php

namespace Voldemort;

class Cluster {

	private $connection;
	private $nodes = array();
	private $socket;

	/**
	 * @param \Voldemort\Connection $connection
	 * @param $clusterResponse
	 */
	function __construct($connection, $clusterResponse = null)
	{
		$this->connection = $connection;

		if ($clusterResponse) {
			$this->setNodesFromResponse($clusterResponse);
		}
	}

	public function makeRequest($storeName, $message, $type, $shouldRoute) {
		return $this->connection->makeRequest($this->getSocket(), $storeName, $message, $type, $shouldRoute);
	}

	private function getSocket() {
		if ($this->socket) {
			return $this->socket;
		}

		if (count($this->nodes) === 0) {
			throw new Exception('No nodes to connect to');
		}

		/**
		 * @var \Voldemort\Node $node
		 */
		foreach ($this->nodes as $node) {
			try {
				$socket = $this->connection->make($node->getHost(), $node->getSocketPort());

				// No Exception, so all OK with the connection
				$this->socket = $socket;
				return $this->socket;
			} catch (\Exception $e) {
				error_log($e->getMessage());
			}
		}

		throw new Exception('Could not connect to any of the '.count($this->nodes).' nodes');
	}

	public function addNode($node) {
		$this->nodes[] = $node;
	}

	public function getNodes() {
		return $this->nodes;
	}

	/**
	 * @param GetResponse $response
	 * @throws Exception
	 */
	public function setNodesFromResponse($response) {
		$this->nodes = array();

		if (count($response->getVersionedList()) === 0) {
			throw new Exception('Invalid response');
		}

		$clusterXml = $response->getVersioned(0)->getValue();
		
		if (!$clusterXml) {
			throw new Exception('Invalid cluster XML');
		}

		$clusterXml = @simplexml_load_string($clusterXml);
		if (!$clusterXml) {
			throw new Exception('Invalid cluster XML');
		}

		foreach ($clusterXml->server as $server) {
			$this->addNode(Node::fromXml($server));
		}
	}

}
