<?php

class ConnectionTest extends PHPUnit_Framework_TestCase {

	public function testMakeThrowsExceptionIfResponseNotOk() {
		$this->setExpectedException('\Voldemort\Exception');

        $mockSocket = $this->getMockBuilder('\Voldemort\Socket')->setMethods(
            array('read', 'write', 'close')
        )->disableOriginalConstructor()->getMock();
        $mockSocket->expects($this->once())->method('write')->with('pb0');
		$mockSocket->expects($this->once())->method('read')->will($this->returnValue('no'));
		$mockSocket->expects($this->once())->method('close');

		$mockFactory = $this->getMock('\Voldemort\SocketFactory', array('createClient'));
		$mockFactory->expects($this->once())->method('createClient')->will($this->returnValue($mockSocket));

		$connection = new \Voldemort\Connection($mockFactory);

		$connection->make('localhost', '6666');
	}

	public function testMakeIfResponseOk() {
		$mockSocket = $this->getMockBuilder('\Voldemort\Socket')->setMethods(array('read', 'write'))->disableOriginalConstructor()->getMock();
		$mockSocket->expects($this->once())->method('write')->with('pb0');
		$mockSocket->expects($this->once())->method('read')->will($this->returnValue('ok'));

		$mockFactory = $this->getMock('\Voldemort\SocketFactory', array('createClient'));
		$mockFactory->expects($this->once())->method('createClient')->will($this->returnValue($mockSocket));

		$connection = new \Voldemort\Connection($mockFactory);

		$this->assertEquals($mockSocket, $connection->make('localhost', '6666'));
	}

	public function testGetFromStore() {
		$mockSocket = $this->getMockBuilder('\Voldemort\Socket')->setMethods(array('read', 'write'))->disableOriginalConstructor()->getMock();
		$mockSocket->expects($this->once())->method('write');
		$mockSocket->expects($this->at(0))->method('read')->will($this->returnValue(pack('N*', 10)));
		$mockSocket->expects($this->at(1))->method('read')->will($this->returnValue('raw data'));

		$connection = new \Voldemort\Connection(null);
		$connection->getFromStore($mockSocket, 'test', 'hello', false);
	}

	public function testPutRequest() {
		$mockSocket = $this->getMockBuilder('\Voldemort\Socket')->setMethods(array('read', 'write'))->disableOriginalConstructor()->getMock();
		$mockSocket->expects($this->once())->method('write');
		$mockSocket->expects($this->once())->method('read')->will($this->returnValue(pack('N*', 0)));

		$connection = new \Voldemort\Connection(null);
		$connection->makeRequest($mockSocket, 'test', (new \Voldemort\PutRequest())->setKey('test')->setVersioned((new \Voldemort\Versioned())->setValue('value')->setVersion(new \Voldemort\VectorClock())), \Voldemort\RequestType::PUT, false);
	}

	public function testMakeRequestWithInvalidType() {
		$this->setExpectedException('\Voldemort\Exception');

		$connection = new \Voldemort\Connection(null);
		$connection->makeRequest(null, 'test', new \Voldemort\GetRequest(), 'INVALID TYPE', false);
	}

}
