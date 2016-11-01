<?php
/**
 * This program is copyright of Curse Inc.
 * View the LICENSE file distributed with the source code
 * for copyright information and available license.
 */
namespace Reflex\Smite;

use GuzzleHttp\Client;
use Onoi\Cache\Cache;
use InvalidArgumentException;

class API {
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
	 * Platform URL to query.
	 * @var string
	 */
	private $platform;

	/**
	 * How long a session with the API is valid. Default: 15 minutes.
	 * Issue a request to getdataused to find out what your limits are.
	 * @var int
	 */
	private $sessionTTL = 900;

	/**
	 * Guzzle Client
	 * @var \GuzzleHttp\Client
	 */
	private $guzzleClient;

	/**
	 * Cache interface
	 * @var \Onoi\Cache\Cache
	 */
	private $cache;

	/**
	 * Custom session from Smite API
	 * @var Session
	 */
	private $session;

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
	 * @return Onoi\Cache\Cache
	 */
	public function getCache() {
		return $this->cache;
	}

	/**
	 * @return Session
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * @return string
	 */
	public function getPlatform() {
		return $this->platform;
	}

	/**
	 * @param string
	 */
	public function setPlatform($platform) {
		$this->platform = $platform;
	}

	/**
	 * Main Constructor for Smite API Class
	 *
	 * @param int $devId
	 * @param string $authKey
	 * @param GuzzleHttp\Client $guzzle [optional]
	 * @throws InvalidArgumentException
	 */
	public function __construct ($devId, $authKey, $platform, Client $guzzle = null){
		if (!$devId) {
			throw new InvalidArgumentException("You need to pass a Dev Id");
		}
		if (!$authKey) {
			throw new InvalidArgumentException("You need to pass an Auth Key");
		}

		$this->devId = $devId;
		$this->authKey = $authKey;
		$this->platform = $platform;
		if (is_null($guzzle)) {
			$this->guzzleClient = new Client();
		} else {
			$this->guzzleClient = $guzzle;
		}
	}

	/**
	 * Getter/setter for whether we return objects or assoc arrays
	 *
	 * @param string $format   optional
	 * @return string
	 */
	public function preferredFormat($format = null) {
		if (is_null($format)) {
			return $this->returnArrays;
		}
		$this->returnArrays = strtolower($format) == 'array';
	}

	/**
	 * Getter/setter for the API session TTL (in seconds)
	 *
	 * @param int $format   optional
	 * @return int
	 */
	public function sessionTTL($ttl = null) {
		if (empty($ttl)) {
			return $this->sessionTTL;
		}
		$this->sessionTTL = (int)$ttl;
	}

	/**
	 * Provide a cache object to use for saving and reusing API sessions
	 * @param Onoi\Cache\Cache $cache
	 */
	public function useCache(Cache $cache) {
		$this->cache = $cache;
	}

	/**
	 * Get or set the language code for API calls.
	 * 
	 * @param string $languageCode       optional
	 * @throws InvalidArgumentException  when given language is not supported
	 */
	public function preferredLanguage($languageCode = null) {
		if (is_null($languageCode)) {
			return $this->languageCode;
		}
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
	 * @throws ApiException when Smite API returns a non-200 response
	 */
	public function request($method) {
		$request = new Request($this, $method);

		// check validity of session and create if needed
		if ($request->requiresSession() && (!$this->session || $this->session->isExpired())) {
			$this->session = new Session($this);
		}

		// get all extra args
		$args = func_get_args();
		array_shift($args); // dump $method off the front
		$request->addArgs($args);

		return $request->send();
	}

	/**
	 * Make a request to the Smite API
	 * allows calling $api->getplayer($playername)
	 * instead of $api->request('getplayer', $playername)
	 *
	 * @param string $method name of method that was called
	 * @param array $params  optional params, in order as needed by the Smite API
	 * @throws ApiException  when Smite API returns a non-200 response
	 */
	public function __call($method, $params) {
		// push method onto args as first
		array_unshift($params, strtolower($method));
		return call_user_func_array([$this, 'request'], $params);
	}
}
