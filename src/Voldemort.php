<?php

require_once('voldemort-client.php');

class Voldemort
{

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

    /**
     * @param \Voldemort\GetRequest|\Voldemort\PutRequest $request
     * @param int $type
     * @return \Voldemort\GetResponse|\Voldemort\PutResponse
     * @throws \Voldemort\Exception
     */
    private function makeRequest($request, $type)
    {
        if (!$this->cluster) {
            throw new \Voldemort\Exception('Cluster not set up');
        }

        $response = $this->cluster->makeRequest($this->storeName, $request, $type, $this->shouldRoute);

        if ($response->hasError()) {
            $error = $response->getError();

            throw new \Voldemort\Exception($error->getErrorMessage(), $error->getErrorCode());
        }

        return $response;
    }

    /**
     * @param string $key
     * @return \Voldemort\GetResponse
     */
    private function _get($key)
    {
        $getRequest = new \Voldemort\GetRequest();
        $getRequest->setKey($key);

        $response = $this->makeRequest($getRequest, \Voldemort\RequestType::GET);

        return $response;
    }

    /**
     * @param string $key
     * @return null|string
     */
    public function get($key)
    {
        $response = $this->_get($key);

        if (count($response->getVersionedList()) > 0) {
            return $response->getVersioned(0)->getValue();
        } else {
            return null;
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return \Voldemort\GetResponse|\Voldemort\PutResponse
     */
    public function put($key, $value)
    {
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

    /**
     * Bootstrap cluster metadata from a list of URLs of nodes in the cluster.
     * The URLs are associative arrays, with keys of "host" and "port".
     *
     * @param array $bootstrapUrls
     * @param string $storeName
     * @param boolean $validateStore
     * @return bool
     * @throws \Voldemort\Exception
     */
    public function bootstrapMetadata($bootstrapUrls, $storeName, $validateStore)
    {
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
                $error = 'Metadata bootstrap from ' . $url['host'] . ':' . $url['port'] . " failed: ";
                $error .= $e->getMessage() . PHP_EOL . $e->getTraceAsString();

                error_log($error);
            }

        }

        $this->closeConnection($socket);

        /**
         * We could not successfully bootstrap off any node
         */
        throw new \Voldemort\Exception('All bootstrap attempts failed');
    }

    /**
     * Setter for shouldRoute. If we don't validate the store in the bootstrapMetadata method,
     * we might need to set this manually.
     *
     * @param bool $shouldRoute
     */
    public function setShouldRoute($shouldRoute)
    {
        $this->shouldRoute = $shouldRoute;
    }

    /**
     * @param \Voldemort\Socket|null $socket
     */
    private function closeConnection($socket = null)
    {
        if ($socket) {
            $socket->close();
        }
    }

    /**
     * @param \Voldemort\Cluster $cluster
     */
    public function setCluster($cluster)
    {
        $this->cluster = $cluster;
    }

    /**
     * @param array $bootstrapUrls
     * @param string $storeName
     * @param bool $validateStore
     * @return Voldemort
     */
    public static function create($bootstrapUrls, $storeName, $validateStore = true)
    {
        $connection = new \Voldemort\Connection(new \Voldemort\SocketFactory());

        $voldemort = new \Voldemort($connection, $storeName);

        $voldemort->bootstrapMetadata($bootstrapUrls, $storeName, $validateStore);

        return $voldemort;
    }

}
