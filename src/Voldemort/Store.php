<?php

namespace Voldemort;

class Store
{

    private $xml;
    private $routing;

    function __construct($xml)
    {
        $this->xml = $xml;
        $this->routing = (string)$xml->routing;
    }

    public function shouldRoute()
    {
        return ($this->routing !== 'client');
    }

    /**
     * @param GetResponse $response
     * @param $storeName
     * @throws Exception
     * @return Store
     */
    public static function getStoreFromResponse($response, $storeName)
    {
        if (count($response->getVersionedList()) === 0) {
            throw new Exception('Invalid response');
        }

        $storesXml = $response->getVersioned(0)->getValue();
        if (!$storesXml) {
            throw new Exception('Invalid stores XML');
        }

        $storesXml = @simplexml_load_string($storesXml);
        if (!$storesXml) {
            throw new Exception('Invalid stores XML');
        }

        foreach ($storesXml->store as $store) {
            if ((string)$store->name === $storeName) {
                return new Store($store);
            }
        }

        throw new Exception('Invalid store ' . $storeName);
    }

}
