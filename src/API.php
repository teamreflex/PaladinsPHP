<?php
namespace Smite;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use InvalidArgumentException;

class API {
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
	private static $queueTierData = [
		'getmatchidsbyqueue',
		'getleagueleaderboard',
		'getleagueseasons',
		'getqueuestats',
	];

	/**
	 * IETF language codes for smite's internal language codes
	 * @var array
	 */
	private static $languageCodeMap = [
		'en' => 1,
		'de' => 2,
		'fr' => 3,
		'es' => 7,
		'es-419' => 9,
		'pt' => 10,
		'ru' => 11,
		'pl' => 12,
		'tr' => 13,
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
		'JoustChallenge' => 441,
		'Assault' => 445,
		'AssaultChallenge' => 446,
		'Joust3v3' => 448,
		'ConquestLeague' => 451,
		'ArenaLeague' => 452,
		'MOTD2' => 465,
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

	/**
	 * When true return assoc arrays instead of stdObject
	 * @var bool
	 */
	private $returnArrays = false;

	/**
	 * Preferred language to return [defaults to english]
	 * @var int
	 */
	private $languageCode = 1;

	/**
	 * Dev Id from Smite API
	 * @var int
	 */
	private $devId;

	/**
	 * Auth Key from Smite API
	 * @var string
	 */
	private $authKey;

	/**
	 * Timestamp when session was created
	 * @var int
	 */
	private $sessionTimestamp;

	/**
	 * Guzzle Client
	 * @var \GuzzleHttp\Client
	 */
	private $guzzleClient;

	/**
	 * Custom session from Smite API.
	 * @var string
	 */
	private $session;

	/**
	 * Smite API URL
	 * @var string
	 */
	private static $smiteAPIUrl = 'http://api.smitegame.com/smiteapi.svc/';

	/**
	 * Getter method for Dev Id
	 * @return int
	 */
	public function getDevId() {
		return $this->devId;
	}

	/**
	 * Getter method for Auth Key
	 * @return string
	 */
	public function getAuthKey() {
		return $this->authKey;
	}

	/**
	 * Getter method for Guzzle Client
	 * @return \GuzzleHttp\Client
	 */
	public function getGuzzleClient() {
		return $this->guzzleClient;
	}

	/**
	 * Main Constructor for Smite API Class
	 *
	 * @param int $devId
	 * @param string $authKey
	 * @param Client $guzzle [optional]
	 * @throws InvalidArgumentException
	 */
	public function __construct ($devId, $authKey, Client $guzzle = null){
		if (!$devId) {
			throw new InvalidArgumentException("You need to pass a Dev Id");
		}
		if (!$authKey) {
			throw new InvalidArgumentException("You need to pass an Auth Key");
		}

		$this->devId = $devId;
		$this->authKey = $authKey;
		if (is_null($guzzle)) {
			$this->guzzleClient = new Client();
		} else {
			$this->guzzleClient = $guzzle;
		}
	}

	/**
	 * Determine format for when we JSON Decode API information.
	 *
	 * @param boolean
	 */
	public function preferFormat($format) {
		$this->returnArrays = strtolower($format) == 'array';
	}

	/**
	 * Set the language code for API calls.
	 * 
	 * @param $languageCode
	 * @throws InvalidArgumentException
	 */
	public function useLanguage($languageCode) {
		if (!isset(self::$languageCodeMap[$languageCode])) {
			throw new InvalidArgumentException("Not a supported language code: $languageCode");
		}
		$this->languageCode = self::$languageCodeMap[$languageCode];
	}

	/**
	 * Make a request to the Smite API
	 *
	 * @param string $method    name of Smite api endpoint with or without leading slash
	 * @param mixed $param1,... optional additional params in order as needed by the Smite API
	 * @throws Exception when Smite API returns a non-200 response
	 */
	public function request($method) {
		// strip leading slash, if present
		if (substr($method, 0, 1) == '/') {
			$method = substr($method, 1);
		}

		// check validity of session and create if needed
		if ($this->sessionRequiredFor($method) && (!$this->session || $this->sessionIsExpired())) {
			$this->createSession();
		}

		// get all extra args
		$args = func_get_args();
		array_shift($args); // dump $method off the front

		// Check to see if the method needs to have any data from the mapping variables.
		if (in_array($method, self::$queueTierData)) {
			$args = $this->applyMaps($method, $args);
		}

		$url = $this->buildRequestUrl($method, $args);

		return json_decode($this->sendRequest($url), $this->returnArrays);
	}

	public function sessionRequiredFor($method) {
		return !($method == 'ping' || $method == 'createsession');
	}

	/**
	 * Loop over a set of arguements and apply mapping values if they are either a queue or tier.
	 *
	 * @param array $arr 	arguements for the URL builder.
	 * @return array $arr 	updated arguments where mappings have been applied.
	 */
	private function applyMaps($arr) {
		foreach ($arr as $call) {
			if (array_key_exists($call, self::$queueMap)) {
				$call = self::$queueMap[$call];
			} else if (array_key_exists($call, self::$tierMap)) {
				$call = self::$tierMap[$call];
			}
		}
		return $arr;
	}

	private function buildRequestUrl($method, $args = []) {
		// automatically add the language code for requests that need it
		if (in_array($method, self::$localizedData)) {
			$args[] = $this->languageCode;
		}

		if ($method != 'ping') {
			$timestamp = self::createTimestamp();
			$signature = $this->createSignature($method, $timestamp);

			$stdArgs = [$this->getDevId(), $signature];
			if ($this->sessionRequiredFor($method)) {
				$stdArgs[] = $this->session;
			}
			$stdArgs[] = $timestamp;
			$args = array_merge($stdArgs, $args);
		}

		// base url for api endpoint, always json data
		$url = self::$smiteAPIUrl . $method . 'json';

		// put the main URL at the beginning of our args
		array_unshift($args, $url);
		return implode('/', $args);
	}

	private function sendRequest($url) {
		try {
			$result = $this->guzzleClient->get($url);
		} catch (TransferException $e) {
			// todo decide on a better way to handle errors
			return '';
		}
		if ($result->getStatusCode() != 200) {
			$respCode = $result->getStatusCode();
			$respBody = $result->getBody();
			throw new Exception("Smite API returned $respCode: ".$respBody);
		}
		return $result->getBody();
	}

	/**
	 * Create unique signature key required by the Smite API.
	 * @param   string Pre-stripped method name
	 * @return  string
	 */
	private function createSignature($method, $timestamp) {
		return md5($this->getDevId().$method.$this->getAuthKey().$timestamp);
	}


	/**
	 * Check to see if our session has expired.
	 *
	 * @return bool
	 */
	private function sessionIsExpired() {
		return time() - $this->sessionTimestamp > 900;
	}

	/**
	 * Perform a create session call to the Smite API.
	 */
	private function createSession() {
		$url = $this->buildRequestUrl('createsession');
		$response = $this->guzzleClient->get($url);
		$body = $response->getBody();
		$body = json_decode($body);
		$this->session = $body->session_id;
		$this->sessionTimestamp = time();
	}

	/**
	 * Get a UTC timestamp
	 * @return string timestamp like 20120927183145
	 */
	private static function createTimestamp() {
		$datetime = new \DateTime('Now', new \DateTimeZone('UTC'));
		return $datetime->format('YmdHis');
	}
}
