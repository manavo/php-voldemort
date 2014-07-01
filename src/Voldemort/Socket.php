<?php

namespace Voldemort;

class Socket extends \Socket\Raw\Socket
{

    /**
     * read up to $length bytes from connect()ed / accept()ed socket
     *
     * @param int $length maximum length to read
     * @throws \Socket\Raw\Exception
     * @return string
     * @see self::recv() if you need to pass flags
     * @uses socket_read()
     */
    public function read($length)
    {
        $data = '';
        $remaining = $length;
        $chunkSize = 4096;

        while ($remaining > 0) {
            if ($chunkSize > $remaining) {
                $chunkSize = $remaining;
            }

            $tmp = @socket_read($this->getResource(), $chunkSize);

            if ($tmp === false) {
                throw \Socket\Raw\Exception::createFromSocketResource($this->getResource());
            }

            $remaining -= strlen($tmp);

            $data .= $tmp;
        }

        return $data;
    }

}
