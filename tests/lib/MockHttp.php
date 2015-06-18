<?php
/**
 * This program is copyright of Curse Inc.
 * View the LICENSE file distributed with the source code
 * for copyright information and available license.
 */
namespace Curse\Smite;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Ring\Client\MockHandler;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

trait MockHttp {
	/**
	 * @var History
	 */
	protected $requestHistory;

	/**
	 * Call during setUp() method
	 */
	protected function setUpMockData() {
		// track requests made
		$this->requestHistory = new History;
		$this->api->getGuzzleClient()->getEmitter()->attach($this->requestHistory);

		// create mock data container
		$this->mockData = new Mock([]);
		$this->api->getGuzzleClient()->getEmitter()->attach($this->mockData);
	}

	/**
	 * Replaces API object with one having a custom guzzle instance that
	 * always returns the given data for every request made
	 * @param int    $code Return code to be returned by mock api
	 * @param string $body Http body to be returned by mock api
	 */
	private function alwaysGetsData($code, $body) {
		// dump existing mock data
		unset($this->mockData);

		$mock = new MockHandler([
			'status' => $code,
			'body' => $body,
		]);
		$guzzle = new Client(['handler' => $mock]);
		if ($this->requestHistory) {
			$guzzle->getEmitter()->attach($this->requestHistory);
		}
		$this->api = new API($this->devId, $this->authKey, $guzzle);
	}

	/**
	 * Queues a mock response to be returned by guzzle
	 * @param int    $code Return code to be returned by mock api
	 * @param string $body Http body to be returned by mock api
	 */
	private function getsData($code, $body) {
		if (!$this->mockData) {
			throw new Exception('Can\'t have individual mocks when data is set to always return');
		}
		$this->mockData->addResponse(
			new Response($code, [], Stream::factory($body))
		);
	}
}
