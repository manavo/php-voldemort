<?php

namespace Voldemort;

class Socket {

    private $socket;

    public function __construct($address) {
        $socket = stream_socket_client($address, $errno, $errstr);

        if (!$socket) {
            throw new Exception('Could not create socket: '.$errstr);
        }

        $this->socket = $socket;
    }

    public function write($data) {
        fwrite($this->socket, $data, strlen($data));
    }

    public function read($length) {
        $data = '';
        $remaining = $length;
        $chunkSize = 4096;

        while ($remaining > 0) {
            if ($chunkSize > $remaining) {
                $chunkSize = $remaining;
            }

            $tmp = fread($this->socket, $chunkSize);

            $remaining -= strlen($tmp);

            $data .= $tmp;
        }

        return $data;
    }

    public function close() {
        fclose($this->socket);
    }

}
