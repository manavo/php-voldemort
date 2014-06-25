<?php

require_once('voldemort-client.php');

class Voldemort {

	/**
	 * @var \Socket\Raw\Factory $socketFactory
	 */
	private $connection = null;

	private $storeName = null;

	/**
	 * @var \Voldemort\Store
	 */
	private $store = null;

	/**
	 * @var \Voldemort\Cluster
	 */
	private $cluster = null;

	private $shouldRoute = false;

	/**
	 * @param \Voldemort\Connection $connection
	 * @param string $storeName
	 */
	function __construct($connection, $storeName)
	{
		$this->connection = $connection;
		$this->storeName = $storeName;
	}

	private function makeRequest($request, $type) {
		if (!$this->cluster) {
			throw new \Voldemort\Exception('Cluster not set up');
		}

		$response = $this->cluster->makeRequest($this->storeName, $request, $type, $this->shouldRoute);

		if ($response->hasError()) {
			throw new \Voldemort\Exception($response->getError()->getErrorMessage(), $response->getError()->getErrorCode());
		}

		return $response;
	}

	/**
	 * @param $key
	 * @return \Voldemort\GetResponse
	 */
	private function _get($key) {
		$getRequest = new \Voldemort\GetRequest();
		$getRequest->setKey($key);

		$response = $this->makeRequest($getRequest, \Voldemort\RequestType::GET);

		return $response;
	}

	public function get($key) {
		$response = $this->_get($key);

		if (count($response->getVersionedList()) > 0) {
			return $response->getVersioned(0)->getValue();
		} else {
			return null;
		}
	}

	public function put($key, $value) {
		$response = $this->_get($key);

		if (count($response->getVersionedList()) > 0) {
			$version = $response->getVersioned(0);
		} else {
			$version = new \Voldemort\Versioned();
		}

		$version->setValue($value);

		$putRequest = new \Voldemort\PutRequest();
		$putRequest->setKey($key);
		$putRequest->setVersioned($version);

		$response = $this->makeRequest($putRequest, \Voldemort\RequestType::PUT);

		return $response;
	}

	public function bootstrapMetadata($bootstrapUrls, $storeName, $validateStore) {
		shuffle($bootstrapUrls);

		$socket = null;

		foreach ($bootstrapUrls as $url) {
			/**
			 * Outside of the try/catch below, since we want this to stop the process, bad config passed in
			 */
			if (empty($url['host']) || empty($url['port'])) {
				throw new \Voldemort\Exception('Invalid bootstrap URL, should be an associative array with keys of "host" and "port"');
			}

			try {
				$socket = $this->connection->make($url['host'], $url['port']);

				$clusterResponse = $this->connection->getFromStore($socket, 'metadata', 'cluster.xml', false);

				$this->cluster = new \Voldemort\Cluster($this->connection, $clusterResponse);

				if ($validateStore) {
					$stores = $this->connection->getFromStore($socket, 'metadata', 'stores.xml', false);

					$this->store = \Voldemort\Store::getStoreFromResponse($stores, $storeName);

					$this->shouldRoute = $this->store->shouldRoute();
				}

				$this->closeConnection($socket);

				return true;
			} catch (Exception $e) {
				/**
				 * Catch and log the Exception. We want to keep looping through the other bootstrap URLs
				 */
				error_log('Metadata bootstrap from ' . $url['host'] . ':' . $url['port'] . " failed: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
			}

		}

		$this->closeConnection($socket);

		throw new \Voldemort\Exception('All bootstrap attempts failed');
	}

	public function setShouldRoute($shouldRoute) {
		$this->shouldRoute = $shouldRoute;
	}

	/**
	 * @param \Socket\Raw\Socket|null $socket
	 */
	private function closeConnection($socket = null) {
		if ($socket) {
			$socket->close();
		}
	}

	public function setCluster($cluster) {
		$this->cluster = $cluster;
	}

	/**
	 * @param array $bootstrapUrls
	 * @param string $storeName
	 * @param bool $validateStore
	 * @return Voldemort
	 */
	public static function create($bootstrapUrls, $storeName, $validateStore = true) {
		$connection = new \Voldemort\Connection(new \Socket\Raw\Factory());

		$voldemort = new \Voldemort($connection, $storeName);

		$voldemort->bootstrapMetadata($bootstrapUrls, $storeName, $validateStore);

		return $voldemort;
	}

}
