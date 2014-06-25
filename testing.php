<?php

require_once('vendor/autoload.php');

try {
	$bootstrapUrls = array(array('host' => '192.168.22.10', 'port' => '6666'));
	$storeName = 'test';

	$voldemort = \Voldemort::create($bootstrapUrls, $storeName);


//	$voldemort->put('test', 'from-code-'.time());
//	echo 'Put done'.PHP_EOL;
//	exit;


	echo $voldemort->get('test');

} catch (Exception $e) {
	echo $e->getMessage().PHP_EOL.$e->getTraceAsString();
}

echo PHP_EOL;
