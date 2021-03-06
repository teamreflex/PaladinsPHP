<?php
/**
 * This program is copyright of Curse Inc.
 * View the LICENSE file distributed with the source code
 * for copyright information and available license.
 */
namespace Curse\Smite;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-06-17 at 16:20:24.
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
	use MockHttp;

	/**
	 * @var API
	 */
	protected $api;

	private $devId = 12345;
	private $authKey = "testauthkey";

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->api = new API($this->devId, $this->authKey);
		$this->setUpMockData();
	}

	/**
	 * @covers Smite\Session::isExpired
	 */
	public function testIsExpired()
	{
		$cache = new \Onoi\Cache\FixedInMemoryLruCache(10);
		$this->api->useCache($cache);

		// create session with empty cache
		$this->getsData(200, '{"session_id":"FAKESESSION"}');
		$session = new Session($this->api);
		$this->assertFalse($session->isExpired(), 'Newly created session should be valid');

		// create session from fresh cache
		$session = new Session($this->api);
		$this->assertFalse($session->isExpired(), 'New session from cache should be valid');

		// create session from stale cache
		$this->getsData(200, '{"session_id":"FRESHSESSION"}');
		$cache->save(Session::$cachingKey, serialize(["STALESESSION", time()-10000]));
		$session = new Session($this->api);
		$this->assertEquals($session->getKey(), 'FRESHSESSION', 'Stale sessions from cache should not be used');
	}

	/**
	 * @covers Smite\Session::getKey
	 */
	public function testGetKey()
	{
		$this->getsData(200, '{"session_id":"FAKESESSION"}');
		$session = new Session($this->api);
		$this->assertEquals($session->getKey(), 'FAKESESSION', 'The session key should be returned');
	}

	/**
	 * @covers Smite\Session::getTimestamp
	 */
	public function testGetTimestamp()
	{
		$this->getsData(200, '{"session_id":"FAKESESSION"}');
		$session = new Session($this->api);
		$this->assertEquals(time(), $session->getTimestamp(), 'The session timestamp should be now');
	}
}
