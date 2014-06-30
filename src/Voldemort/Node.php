<?php

namespace Voldemort;

class Node
{

    private $id;
    private $host;
    private $socketPort;

    /**
     * @param int $id
     * @param string $host
     * @param int $socketPort
     */
    function __construct($id, $host, $socketPort)
    {
        $this->id = $id;
        $this->host = $host;
        $this->socketPort = $socketPort;
    }

    /**
     * @param $serverXml
     * @return static
     */
    public static function fromXml($serverXml)
    {
        $id = (int)$serverXml->id;
        $host = (string)$serverXml->host;
        $socketPort = (int)$serverXml->{'socket-port'};

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
     * @return int
     */
    public function getSocketPort()
    {
        return $this->socketPort;
    }

}
