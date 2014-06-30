<?php

class NodeTest extends PHPUnit_Framework_TestCase
{

    public function testFromXml()
    {

        $xml = <<<EOQ
<server>
    <id>0</id>
    <host>192.168.22.10</host>
    <http-port>8081</http-port>
    <socket-port>6666</socket-port>
    <admin-port>6667</admin-port>
    <partitions>0, 1</partitions>
</server>
EOQ;


        $node = \Voldemort\Node::fromXml(simplexml_load_string($xml));

        $this->assertEquals('192.168.22.10', $node->getHost());
        $this->assertEquals('6666', $node->getSocketPort());

    }

}
