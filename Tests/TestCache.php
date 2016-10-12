<?php

require_once('../Cache.php');

use PHPUnit\Framework\TestCase;
use RedundantMemcached\Cache;

class ConnectionTest extends TestCase
{
	public function __construct()
	{
		//Both of these servers should be up and running
		$this->config_alive = [
			'memcached1_ip' => '',
			'memcached1_port' => 11211,
			'memcached2_ip' => '',
			'memcached2_port' => 11211
		];

		//One of these servers has to be down
		$this->config_partial = [
			'memcached1_ip' => '',
			'memcached1_port' => 11211,
			'memcached2_ip' => '111.111.111.111',
			'memcached2_port' => 11211
		];

		//Both of these servers have to be down
		$this->config_dead = [
			'memcached1_ip' => '111.111.111.111',
			'memcached1_port' => 11211,
			'memcached2_ip' => '222.222.222.222',
			'memcached2_port' => 11211
		];

		if (empty($this->config_alive['memcached1_ip']))
			throw new \Exception('Please provide your memcached configuration before starting this test');

	}

	//Both servers are up and running
	public function testSuccess()
	{
		$cache = new Cache($this->config_alive);

		$cache->set('testHash', 'testValue', 15);
		$testHash = $cache->get('testHash');

		$this->assertEquals('testValue', $testHash);
	}

	//Manually deleting one key from the 1st server
	public function testMissing()
	{
		$memc = new \Memcached;
        $memc->addServer($this->config_alive['memcached1_ip'], $this->config_alive['memcached1_port']);
        $memc->delete('testHash');

		$cache = new Cache($this->config_alive);
		$testHash = $cache->get('testHash');

		//Cache should return null in case if values are inconsistent among alive memcached servers
		$this->assertEquals(null, $testHash);
	}

	//The value of the key is returned in case only 1 server is running
	public function testOneDown()
	{
		$cache = new Cache($this->config_partial);

		$cache->set('testHash2', 'testValue', 5);
		$testHash = $cache->get('testHash2');

		$this->assertEquals('testValue', $testHash);
	}

	//Both servers are down
	public function testBothDown()
	{
		$cache = new Cache($this->config_dead);

		$cache->set('testHash3', 'testValue', 5);
		$testHash = $cache->get('testHash3');

		$this->assertEquals(null, $testHash);
	}

}





?>