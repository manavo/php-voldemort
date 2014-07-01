<?php

namespace Voldemort;

class Connection
{

    private $socketFactory;

    /**
     * @param SocketFactory $socketFactory
     */
    function __construct($socketFactory)
    {
        $this->socketFactory = $socketFactory;
    }

    /**
     * @param string $host
     * @param int $port
     * @return \Voldemort\Socket
     * @throws Exception
     */
    public function make($host, $port)
    {
        $address = 'tcp://' . $host . ':' . $port;

        $socket = $this->socketFactory->createClient($address);

        /**
         * Protocol negotiation to be Protocol Buffers
         */
        $protocol = 'pb0';

        $socket->write($protocol);
        if ($socket->read(2) !== 'ok') {
            $socket->close();

            throw new Exception('Server does not understand the protocol ' . $protocol);
        }

        return $socket;
    }

    /**
     * @param \Voldemort\Socket $socket
     * @param string $storeName
     * @param string $key
     * @param boolean $shouldRoute
     * @return GetResponse
     */
    public function getFromStore($socket, $storeName, $key, $shouldRoute)
    {
        $getRequest = new GetRequest();
        $getRequest->setKey($key);

        $response = $this->makeRequest($socket, $storeName, $getRequest, RequestType::GET, $shouldRoute);

        return $response;
    }

    /**
     * @param \Voldemort\Socket $socket
     * @param string $storeName
     * @param \DrSlump\Protobuf\Message $message
     * @param int $type
     * @param boolean $shouldRoute
     * @throws Exception
     * @return GetResponse|PutResponse
     */
    public function makeRequest($socket, $storeName, $message, $type, $shouldRoute)
    {
        $request = new VoldemortRequest();
        $request->setShouldRoute($shouldRoute);
        $request->setStore($storeName);

        $request->setType($type);
        if ($type === RequestType::GET) {
            $request->setGet($message);
            $response = new GetResponse();
        } else {
            if ($type === RequestType::PUT) {
                $request->setPut($message);
                $response = new PutResponse();
            } else {
                throw new Exception('Unsupported type ' . $type);
            }
        }

        $rawData = $this->send($socket, $request->serialize());

        $response->parse($rawData);

        return $response;
    }

    /**
     * @param \Voldemort\Socket $socket
     * @param string $input
     * @return string
     */
    private function send($socket, $input)
    {
        $socket->write(pack('N*', strlen($input)) . $input);

        $count = $socket->read(4);

        $aCount = unpack('N*', $count);
        $readBytes = array_shift($aCount);
        if ($readBytes) {
            return $socket->read($readBytes);
        } else {
            return null;
        }
    }

}
