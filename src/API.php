<?php
namespace Smite;

class API {
	/**
	 * IETF language codes for smite's internal language codes
	 * @var array
	 */
	private static $languageCodeMap = [
		1 => 'en',
		2 => 'de',
		3 => 'fr',
		7 => 'es',
		9 => 'es-419',
		10 => 'pt',
		11 => 'ru',
		12 => 'pl',
		13 => 'tr',
	];

	/**
	 * String mapping for Smite queue types
	 * @var array
	 */
	private static $queueMap = [
		423 => 'Conquest5v5',
		424 => 'NoviceQueue',
		426 => 'Conquest',
		427 => 'Practice',
		429 => 'ConquestChallenge',
		430 => 'ConquestRanked',
		433 => 'Domination',
		434 => 'MOTD1',
		435 => 'Arena',
		438 => 'ArenaChallenge',
		439 => 'DominationChallenge',
		440 => 'JoustLeague',
		441 => 'JoustChallenge',
		445 => 'Assault',
		446 => 'AssaultChallenge',
		448 => 'Joust3v3',
		451 => 'ConquestLeague',
		452 => 'ArenaLeague',
		465 => 'MOTD2'
	];

	/**
	 * String mapping for ranked tiers to Smite's internal tier ID
	 * @var array
	 */
	private static $tierMap = [
		 1 => 'Bronze5',
		 2 => 'Bronze4',
		 3 => 'Bronze3',
		 4 => 'Bronze2',
		 5 => 'Bronze1',
		 6 => 'Silver5',
		 7 => 'Silver4',
		 8 => 'Silver3',
		 9 => 'Silver2',
		10 => 'Silver1',
		11 => 'Gold5',
		12 => 'Gold4',
		13 => 'Gold3',
		14 => 'Gold2',
		15 => 'Gold1',
		16 => 'Platinum5',
		17 => 'Platinum4',
		18 => 'Platinum3',
		19 => 'Platinum2',
		20 => 'Platinum1',
		21 => 'Diamond5',
		22 => 'Diamond4',
		23 => 'Diamond3',
		24 => 'Diamond2',
		25 => 'Diamond1',
		26 => 'Masters1'
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
	private static $smiteAPIUrl = 'http://api.smitegame.com/smiteapi.svc';

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
	 * @param $devId
	 * @param $authKey
	 * @throws \Exception
	 */
	public function __construct ($devId, $authKey){
		if (!$devId) {
			throw new \Exception("You need to pass a Dev Id");
		}

		if (!$authKey) {
			throw new \Exception("You need to pass an Auth Key");
		}

		$this->devId = $devId;
		$this->authKey = $authKey;
		$this->guzzleClient = new \GuzzleHttp\Client();
	}

	/**
	 * Determine format for when we JSON Decode API information.
	 * 
	 * @param boolean
	 */
	public function preferFormat($format) {
		$this->returnArrays = strtolower($format) == 'array';
	}

	public function useLanguage($languageCode) {

	}

	public function request() {
		if ($this->sessionIsExpired() || !$this->session) {
			$this->session = $this->createSession();
		}

		$args = func_get_args();
		$method = substr($args[0], 1);
		$signature = $this->createSignature($method);

		// TODO:: Finish Request implementation.
	}

	/**
	 * Create unique signature key required by the Smite API.
	 *
	 * @param	string Pre-stripped method name
	 * @return	string
	 */
	private function createSignature($method) {
		return md5($this->getDevId().$method.$this->getAuthKey().self::createTimestamp());
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
	 *
	 * @return string	Session ID
	 */
	private function createSession() {
		$signature = $this->createSignature('createsession');
		$url = self::$smiteAPIUrl."/createsessionjson/".$this->getDevId()."/$signature/".self::createTimestamp();
		$response = $this->guzzleClient->get($url);
		$body = $response->getBody();
		return $body->session_id;
	}

	/**
	 * Get a UTC timestamp
	 * @return string timestamp like 20120927183145
	 */
	private static function createTimestamp() {
		$datetime = new \DateTime('Now', \DateTimeZone::UTC);
		return $datetime->format('YmdHis');
	}
}
