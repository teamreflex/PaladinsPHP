<?php
namespace Smite;

class API {
	private $returnArrays = false;

	// TODO: Map Language Code
	private $languageCode = 1;

	private $devId;

	private $authKey;

	private $sessionTimestamp;

	public function getDevId() {
		return $this->devId;
	}

	public function getAuthKey() {
		return $this->authKey;
	}

	public function __construct ($devId, $authKey){
		if (!$devId) {
			throw new \Exception("You need to pass a Dev Id");
		}

		if (!$authKey) {
			throw new \Exception("YOu need to pass an Auth Key");
		}

		$this->devId = $devId;
		$this->authKey = $authKey;
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
	}

	/**
	 * @param	string Pre-stripped method name
	 * @return	string
	 */
	private function createSignature($method) {
		$datetime = new \DateTime('Now', \DateTimeZone::UTC);
		$timestamp = $datetime->format('YmdHis');
		return md5($this->getDevId().$method.$this->getAuthKey().$timestamp);
	}

	private function checkSession() {
		return time() - $this->sessionTimestamp > 900;
	}

	private function createSession() {

	}
}