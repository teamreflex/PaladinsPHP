<?php
namespace Smite;

class API {
	private $returnArrays = false;

	// TODO: Map Language Code
	private $languageCode = 1;

	private $devId;

	private $authKey;

	private $sessionTimestamp;

	private $guzzleClient;

	private static $smiteAPIUrl = 'http://api.smitegame.com/smiteapi.svc';

	public function getDevId() {
		return $this->devId;
	}

	public function getAuthKey() {
		return $this->authKey;
	}

	public function getGuzzleClient() {
		return $this->guzzleClient;
	}

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

	public function preferFormat($format) {
		$this->returnArrays = strtolower($format) == 'array';
	}

	public function useLanguage($languageCode) {
		// TODO: Write language mapping function
	}

	public function request() {
		$args = func_get_args();
		$method = substr($args[0], 1);
		$signature = $this->createSignature($method);
		if ($this->checkSession()) {
			$session = $this->createSession();
		}
		// TODO:: Finish Request implementation.
	}

	/**
	 * @param	string Pre-stripped method name
	 * @return	string
	 */
	private function createSignature($method) {
		return md5($this->getDevId().$method.$this->getAuthKey().self::createTimestamp());
	}

	private function checkSession() {
		return time() - $this->sessionTimestamp > 900;
	}

	private function createSession() {

	}

	private static function createTimestamp() {
		$datetime = new \DateTime('Now', \DateTimeZone::UTC);
		return $datetime->format('YmdHis');
	}
}