<?php
/**
 * This program is copyright of Curse Inc.
 * View the LICENSE file distributed with the source code
 * for copyright information and available license.
 */
namespace Reflex\Smite;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

/**
 * Class to manage individual requests to the Smite API
 */
class Request {
	/**** STATIC STUFF ****/

	/**
	 * Smite API URL
	 * @var string
	 */
	private static $smiteAPIUrl = 'http://api.smitegame.com/smiteapi.svc/';

	/**
	 * List of methods that need a language code appended
	 * @var array
	 */
	private static $localizedData = [
		'getgods',
		'getgodrecommendeditems',
		'getitems',
	];

	/**
	 * List of methods that need to be compared to queue and tier mapping variables.
	 * @var array
	 */
	private static $mapArgsForMethods = [
		'getmatchidsbyqueue',
		'getleagueleaderboard',
		'getleagueseasons',
		'getqueuestats',
	];

	/**
	 * String mapping for Smite queue types
	 * @var array
	 */
	private static $queueMap = [
		'Conquest5v5' => 423,
		'NoviceQueue' => 424,
		'Conquest' => 426,
		'Practice' => 427,
		'ConquestChallenge' => 429,
		'ConquestRanked' => 430,
		'Domination' => 433,
		'MOTD1' => 434,
		'Arena' => 435,
		'ArenaChallenge' => 438,
		'DominationChallenge' => 439,
		'JoustLeague' => 440,
		'JoustRanked' => 440,
		'JoustChallenge' => 441,
		'Assault' => 445,
		'AssaultChallenge' => 446,
		'Joust3v3' => 448,
		'JoustRanked3v3' => 450,
		'ConquestLeague' => 451,
		'ConquestRanked' => 451,
		'ArenaLeague' => 452,
		'ArenaRanked' => 452,
		'MOTD2' => 465,
		'Clash' => 466,
		'ClashChallenge' => 467,
	];

	/**
	 * String mapping for ranked tiers to Smite's internal tier ID
	 * @var array
	 */
	private static $tierMap = [
		'Bronze5' => 1,
		'Bronze4' => 2,
		'Bronze3' => 3,
		'Bronze2' => 4,
		'Bronze1' => 5,
		'Silver5' => 6,
		'Silver4' => 7,
		'Silver3' => 8,
		'Silver2' => 9,
		'Silver1' => 10,
		'Gold5' => 11,
		'Gold4' => 12,
		'Gold3' => 13,
		'Gold2' => 14,
		'Gold1' => 15,
		'Platinum5' => 16,
		'Platinum4' => 17,
		'Platinum3' => 18,
		'Platinum2' => 19,
		'Platinum1' => 20,
		'Diamond5' => 21,
		'Diamond4' => 22,
		'Diamond3' => 23,
		'Diamond2' => 24,
		'Diamond1' => 25,
		'Masters1' => 26,
	];

	/**** END STATIC STUFF ****/

	/**
	 * @var API
	 */
	private $api;

	/**
	 * @var string
	 */
	private $method;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * @var DateTime
	 */
	private $timestamp;

	/**
	 * Individual components of the request url
	 * @var string[]
	 */
	private $args = [];

	/**
	 * @param API    $api       API object containing dev ID, auth key, etc
	 * @param string $method    name of Smite api endpoint with or without leading slash
	 */
	function __construct(API $api, $method) {
		$this->api = $api;

		// strip leading slash, if present
		if (substr($method, 0, 1) == '/') {
			$method = substr($method, 1);
		}
		$this->method = $method;
	}
	/**
	 * @param array $args All extra parameters for the API request, in order
	 */
	function addArgs($args = []) {
		$this->args = $args;
		// Check to see if the method needs to have any data from the mapping variables.
		if (in_array($this->method, self::$mapArgsForMethods)) {
			$this->mapArgs();
		}
	}

	/**
	 * Returns the full URL requested
	 * @return string
	 */
	public function getRequestedUrl() {
		if (empty($this->url)) {
			$this->buildRequestUrl();
		}
		return $this->url;
	}

	/**
	 * Returns the timestamp from the request signature as a DateTime object
	 * @return DateTime
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * @return boolean true if this request needs a valid session
	 */
	public function requiresSession() {
		return !($this->method == 'ping' || $this->method == 'createsession');
	}

	/**
	 * Constructs the request URL that will be used when the request is sent
	 * @return string
	 */
	private function buildRequestUrl() {
		// automatically add the language code for requests that need it
		if (in_array($this->method, self::$localizedData)) {
			$this->args[] = $this->api->preferredLanguage();
		}

		// ping is only request that requires no args
		if ($this->method != 'ping') {
			$this->timestamp = new \DateTime('Now', new \DateTimeZone('UTC'));

			// all requests need dev id and signature
			$stdArgs = [$this->api->getDevId(), $this->createSignature()];
			// some requests need a session
			if ($this->requiresSession()) {
				$stdArgs[] = $this->api->getSession()->getKey();
			}
			// all requests need a timestamp
			$stdArgs[] = $this->timestamp->format('YmdHis');
			// add these standard args ahead of the user-provided args
			$this->args = array_merge($stdArgs, $this->args);
		}

		// base url for api endpoint, always json data
		$url = $this->api->getPlatform . $this->method . 'json';

		// put the main URL at the beginning of our args
		array_unshift($this->args, $url);
		$this->url = implode('/', $this->args);
	}

	/**
	 * Create unique signature key required by the Smite API
	 * @return  string
	 */
	private function createSignature() {
		return md5(
			$this->api->getDevId()
			.$this->method
			.$this->api->getAuthKey()
			.$this->timestamp->format('YmdHis')
		);
	}

	/**
	 * Loop over a set of arguements and apply mapping values if they are either a queue or tier.
	 *
	 * @param array $arr    arguements for the URL builder
	 */
	private function mapArgs() {
		foreach ($this->args as &$call) {
			if (array_key_exists($call, self::$queueMap)) {
				$call = self::$queueMap[$call];
			} else if (array_key_exists($call, self::$tierMap)) {
				$call = self::$tierMap[$call];
			}
		}
	}

	/**
	 * Sends the request to the remote
	 * @throws Smite\ApiException
	 */
	public function send() {
		$this->buildRequestUrl();

		try {
			$result = $this->api->getGuzzleClient()->get($this->url);
		} catch (TransferException $e) {
			throw new ApiException($e->getMessage(), $e->getCode(), $e);
		}
		if ($result->getStatusCode() != 200) {
			$respCode = $result->getStatusCode();
			$respBody = $result->getBody();
			throw new ApiException("Smite API returned $respCode: ".$respBody);
		}
		return json_decode($result->getBody(), $this->api->preferredFormat());
	}
}
