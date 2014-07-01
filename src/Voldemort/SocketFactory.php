<?php

namespace Voldemort;

class SocketFactory {

    public function createClient($address) {
        return new Socket($address);
    }

}
