<?php

namespace Voldemort;

class Cluster
{

    private $connection;
    private $nodes = array();
    private $socket;

    /**
     * @var Node
     */
    private $currentNode;

    /**
     * @param Connection $connection
     * @param $clusterResponse
     */
    function __construct($connection, $clusterResponse = null)
    {
        $this->connection = $connection;

        if ($clusterResponse) {
            $this->setNodesFromResponse($clusterResponse);
        }
    }

    /**
     * @return Node
     */
    public function getCurrentNode()
    {
        if (!$this->socket || !$this->currentNode) {
            /**
             * Forces to connect, so that we have a current node
             */
            $this->getSocket();
        }

        return $this->currentNode;
    }

    /**
     * @param array $entries
     * @throws Exception
     * @return bool
     */
    private function incrementExistingEntry($entries)
    {
        /**
         * @var ClockEntry $entry
         */
        foreach ($entries as $entry) {
            if ($entry->getNodeId() === $this->getCurrentNode()->getId()) {
                $entry->setVersion($entry->getVersion() + 1);
                return true;
            }
        }

        return false;
    }

    /**
     * @return ClockEntry
     */
    private function getNewEntry()
    {
        $clockEntry = new ClockEntry();
        $clockEntry->setVersion(1);
        $clockEntry->setNodeId($this->getCurrentNode()->getId());
        return $clockEntry;
    }

    /**
     * @param Versioned $version
     * @return Versioned
     */
    private function getNextVersion($version)
    {
        if ($version->hasVersion()) {
            $vectorClock = $version->getVersion();
        } else {
            $vectorClock = new VectorClock();
        }

        if ($vectorClock->hasEntries() && count($vectorClock->getEntriesList()) > 0) {
            if ($this->incrementExistingEntry($vectorClock->getEntriesList()) === false) {
                // Failed to increment, create new one
                $clockEntry = $this->getNewEntry();
                $vectorClock->setEntries($clockEntry, count($vectorClock->getEntriesList()));
            }
        } else {
            $clockEntry = $this->getNewEntry();
            $vectorClock->setEntries($clockEntry, 0);
        }

        $timestamp = microtime(true) * 1000;
        $vectorClock->setTimestamp($timestamp);

        $version->setVersion($vectorClock);

        return $version;
    }

    /**
     * @param string $storeName
     * @param GetRequest|PutRequest $message
     * @param int $type
     * @param bool $shouldRoute
     * @return GetResponse|PutResponse
     */
    public function makeRequest($storeName, $message, $type, $shouldRoute)
    {
        if ($type === RequestType::PUT) {
            $version = $this->getNextVersion($message->getVersioned());

            $message->setVersioned($version);
        }

        return $this->connection->makeRequest($this->getSocket(), $storeName, $message, $type, $shouldRoute);
    }

    /**
     * @return \Voldemort\Socket
     * @throws Exception
     */
    private function getSocket()
    {
        if ($this->socket) {
            return $this->socket;
        }

        if (count($this->nodes) === 0) {
            throw new Exception('No nodes to connect to');
        }

        /**
         * @var Node $node
         */
        foreach ($this->nodes as $node) {
            try {
                $socket = $this->connection->make($node->getHost(), $node->getSocketPort());

                $this->currentNode = $node;

                // No Exception, so all OK with the connection
                $this->socket = $socket;
                return $this->socket;
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
        }

        throw new Exception('Could not connect to any of the ' . count($this->nodes) . ' nodes');
    }

    /**
     * @param Node $node
     */
    public function addNode($node)
    {
        $this->nodes[] = $node;
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * @param GetResponse $response
     * @throws Exception
     */
    public function setNodesFromResponse($response)
    {
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

        shuffle($this->nodes);
    }

}
