<?php

class VoldemortTest extends PHPUnit_Framework_TestCase
{

    public function badBootstrapUrlsProvider()
    {
        return array(
            array(array()),
            array(array('host' => 'localhost')),
            array(array('port' => '6666')),
        );
    }

    /**
     * @dataProvider badBootstrapUrlsProvider
     */
    public function testExceptionThrownWithInvalidBoostrapUrlsArray($bootstrapUrls)
    {
        $this->setExpectedException('\Voldemort\Exception');

        Voldemort::create($bootstrapUrls, 'test');
    }

    public function testMakingRequestWithInvalidClusterThrowsException()
    {
        $this->setExpectedException('\Voldemort\Exception');

        $voldemort = new \Voldemort(null, 'test');
        $voldemort->get('hello');
    }

    public function testGetWithExistingKey()
    {
        $cluster = $this->getMockBuilder('\Voldemort\Cluster')->setMethods(
            array('makeRequest')
        )->disableOriginalConstructor()->getMock();

        $expectedValue = 'value';

        $response = new \Voldemort\GetResponse();
        $response->setVersioned((new \Voldemort\Versioned())->setValue($expectedValue), 0);

        $cluster->expects($this->once())->method('makeRequest')->will($this->returnValue($response));


        $voldemort = new \Voldemort(null, 'test');
        $voldemort->setCluster($cluster);


        $this->assertEquals($expectedValue, $voldemort->get('key'));
    }

    public function testGetWithNonExistingKey()
    {
        $cluster = $this->getMockBuilder('\Voldemort\Cluster')->setMethods(
            array('makeRequest')
        )->disableOriginalConstructor()->getMock();

        $response = new \Voldemort\GetResponse();

        $cluster->expects($this->once())->method('makeRequest')->will($this->returnValue($response));


        $voldemort = new \Voldemort(null, 'test');
        $voldemort->setCluster($cluster);


        $this->assertNull($voldemort->get('key'));
    }

    public function testBootstrapClusterMetadata()
    {
        $boostrapUrls = array(array('host' => 'localhost', 'port' => '6666'));

        $connection = $this->getMockBuilder('\Voldemort\Connection')->setMethods(
            array('make', 'getFromStore')
        )->disableOriginalConstructor()->getMock();

        $connection->expects($this->once())->method('make')->will(
            $this->returnValue($this->getMockBuilder('\Voldemort\Socket')->disableOriginalConstructor()->getMock())
        );
        $connection->expects($this->once())->method('getFromStore')->will($this->returnValue(null));

        $voldemort = new \Voldemort($connection, 'test');

        $this->assertTrue($voldemort->bootstrapMetadata($boostrapUrls, 'test', false));
    }

    public function testBootstrapMetadataThrowsExceptions()
    {
        $this->setExpectedException('\Voldemort\Exception');

        $boostrapUrls = array(
            array('host' => 'localhost', 'port' => '6666'),
            array('host' => 'localhost', 'port' => '6666'),
            array('host' => 'localhost', 'port' => '6666')
        );

        $connection = $this->getMockBuilder('\Voldemort\Connection')->setMethods(
            array('make', 'getFromStore')
        )->disableOriginalConstructor()->getMock();

        $connection->expects($this->exactly(count($boostrapUrls)))->method('make')->will(
            $this->throwException(new \Exception())
        );

        $voldemort = new \Voldemort($connection, 'test');

        $this->assertTrue($voldemort->bootstrapMetadata($boostrapUrls, 'test', false));
    }

    public function testBootstrapStoreMetadata()
    {
        $xml = <<<EOQ
<stores>
  <store>
    <name>test</name>
    <persistence>bdb</persistence>
    <description>Test store</description>
    <owners>harry@hogwarts.edu, hermoine@hogwarts.edu</owners>
    <routing-strategy>consistent-routing</routing-strategy>
    <routing>client</routing>
    <replication-factor>1</replication-factor>
    <required-reads>1</required-reads>
    <required-writes>1</required-writes>
    <hinted-handoff-strategy>consistent-handoff</hinted-handoff-strategy>
    <key-serializer>
      <type>string</type>
    </key-serializer>
    <value-serializer>
      <type>string</type>
    </value-serializer>
  </store>
</stores>
EOQ;

        $boostrapUrls = array(array('host' => 'localhost', 'port' => '6666'));

        $connection = $this->getMockBuilder('\Voldemort\Connection')->setMethods(
            array('make', 'getFromStore')
        )->disableOriginalConstructor()->getMock();

        $connection->expects($this->once())->method('make')->will(
            $this->returnValue($this->getMockBuilder('\Voldemort\Socket')->disableOriginalConstructor()->getMock())
        );
        $connection->expects($this->at(0))->method('getFromStore')->will($this->throwException(new Exception('hello')));
        $connection->expects($this->at(1))->method('getFromStore')->will($this->returnValue(null));
        $connection->expects($this->at(2))->method('getFromStore')->will(
            $this->returnValue(
                (new \Voldemort\GetResponse())->setVersioned((new \Voldemort\Versioned())->setValue($xml), 0)
            )
        );

        $voldemort = new \Voldemort($connection, 'test');

        $this->assertTrue($voldemort->bootstrapMetadata($boostrapUrls, 'test', true));
    }

    public function testExceptionThrownIfErrorInRequest()
    {
        $this->setExpectedException('\Voldemort\Exception', 'Error in request', 1);

        $cluster = $this->getMockBuilder('\Voldemort\Cluster')->setMethods(
            array('makeRequest')
        )->disableOriginalConstructor()->getMock();
        $cluster->expects($this->once())->method('makeRequest')->will(
            $this->returnValue(
                (new \Voldemort\PutResponse())->setError(
                    (new \Voldemort\Error())->setErrorCode(1)->setErrorMessage('Error in request')
                )
            )
        );

        $voldemort = new \Voldemort(null, 'test');
        $voldemort->setCluster($cluster);

        $voldemort->get('testing-key');
    }

}
