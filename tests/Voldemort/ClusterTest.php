<?php

class ClusterTest extends VoldemortTestCase {

	public function testSocketGetsReused() {
		$requestCount = 10;

		$connection = $this->getMockBuilder('\Voldemort\Connection')->setMethods(array('make', 'makeRequest'))->disableOriginalConstructor()->getMock();

		/**
		 * Assert that once we get the socket once, we keep reusing it
		 */
		$connection->expects($this->once())->method('make')->will($this->returnValue(new stdClass()));
		$connection->expects($this->exactly($requestCount))->method('makeRequest');

		$cluster = new \Voldemort\Cluster($connection);

		$cluster->addNode(new \Voldemort\Node('localhost', '6666'));

		for ($i=0; $i<$requestCount; $i++) {
			$cluster->makeRequest('test', new \Voldemort\GetRequest(), \Voldemort\RequestType::GET, false);
		}
	}

	public function testExceptionThrownIfNoNodesExist() {
		$this->setExpectedException('\Voldemort\Exception');

		$connection = $this->getMockBuilder('\Voldemort\Connection')->setMethods(array('make', 'makeRequest'))->disableOriginalConstructor()->getMock();

		$cluster = new \Voldemort\Cluster($connection);
		$cluster->makeRequest('test', new \Voldemort\GetRequest(), \Voldemort\RequestType::GET, false);
	}

	public function testExceptionThrownWhenGettingSocket() {
		$this->setExpectedException('\Voldemort\Exception');

		$connection = $this->getMockBuilder('\Voldemort\Connection')->setMethods(array('make', 'makeRequest'))->disableOriginalConstructor()->getMock();

		$connection->expects($this->once())->method('make')->will($this->throwException(new Exception()));

		$cluster = new \Voldemort\Cluster($connection);
		$cluster->addNode(new \Voldemort\Node('localhost', '6666'));

		$cluster->makeRequest('test', new \Voldemort\GetRequest(), \Voldemort\RequestType::GET, false);
	}

	public function testSetNodesFromResponseWithInvalidResponse() {
		$this->setExpectedException('\Voldemort\Exception');

		$cluster = new \Voldemort\Cluster(null);

		$response = new \Voldemort\GetResponse();

		$cluster->setNodesFromResponse($response);
	}

	public function testSetNodesFromResponseWithEmptyValue() {
		$this->setExpectedException('\Voldemort\Exception');

		$cluster = new \Voldemort\Cluster(null);

		$response = new \Voldemort\GetResponse();
		$response->setVersioned((new \Voldemort\Versioned())->setValue(''), 0);

		$cluster->setNodesFromResponse($response);
	}

	public function testSetNodesFromResponseWithInvalidXml() {
		$this->setExpectedException('\Voldemort\Exception');

		$cluster = new \Voldemort\Cluster(null);

		$response = new \Voldemort\GetResponse();
		$response->setVersioned((new \Voldemort\Versioned())->setValue('this is definitely not XML!!!'), 0);

		$cluster->setNodesFromResponse($response);
	}

	public function testSetNodesFromResponseWithValidXml() {
		$xml = <<<EOQ
<cluster>
  <name>mycluster</name>
  <server>
    <id>0</id>
    <host>192.168.22.10</host>
    <http-port>8081</http-port>
    <socket-port>6666</socket-port>
    <admin-port>6667</admin-port>
    <partitions>0, 1</partitions>
  </server>
  <server>
    <id>0</id>
    <host>192.168.22.10</host>
    <http-port>8081</http-port>
    <socket-port>6666</socket-port>
    <admin-port>6667</admin-port>
    <partitions>0, 1</partitions>
  </server>
  <server>
    <id>0</id>
    <host>192.168.22.10</host>
    <http-port>8081</http-port>
    <socket-port>6666</socket-port>
    <admin-port>6667</admin-port>
    <partitions>0, 1</partitions>
  </server>
</cluster>
EOQ;

		$response = new \Voldemort\GetResponse();
		$response->setVersioned((new \Voldemort\Versioned())->setValue($xml), 0);

		$cluster = new \Voldemort\Cluster(null, $response);

		$this->assertEquals(3, count($cluster->getNodes()));
	}

}
