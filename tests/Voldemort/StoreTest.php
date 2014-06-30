<?php

class StoreTest extends PHPUnit_Framework_TestCase
{

    public function testFromResponseNoVersions()
    {
        $this->setExpectedException('\Voldemort\Exception');

        \Voldemort\Store::getStoreFromResponse(new \Voldemort\GetResponse(), 'test');
    }

    public function testFromResponseNoXml()
    {
        $this->setExpectedException('\Voldemort\Exception');

        \Voldemort\Store::getStoreFromResponse(
            (new \Voldemort\GetResponse())->setVersioned((new \Voldemort\Versioned())->setValue(''), 0),
            'test'
        );
    }

    public function testFromResponseInvalidXml()
    {
        $this->setExpectedException('\Voldemort\Exception');

        \Voldemort\Store::getStoreFromResponse(
            (new \Voldemort\GetResponse())->setVersioned(
                (new \Voldemort\Versioned())->setValue('THIS IS NOT XML!!!'),
                0
            ),
            'test'
        );
    }

    public function testFromResponseInvalidStore()
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


        $this->setExpectedException('\Voldemort\Exception');

        \Voldemort\Store::getStoreFromResponse(
            (new \Voldemort\GetResponse())->setVersioned((new \Voldemort\Versioned())->setValue($xml), 0),
            'testing-invalid-store'
        );

    }

    public function testFromResponseValidStore()
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


        $this->assertTrue(
            \Voldemort\Store::getStoreFromResponse(
                (new \Voldemort\GetResponse())->setVersioned((new \Voldemort\Versioned())->setValue($xml), 0),
                'test'
            ) instanceof \Voldemort\Store
        );

    }

}
