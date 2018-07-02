<?php
/**
 * This program is copyright of Curse Inc.
 * View the LICENSE file distributed with the source code
 * for copyright information and available license.
 */
namespace Reflex\Paladins;

class Session {
	/**
	 * @var string
	 */
	public $cacheKey;

	/**
	 * Timestamp when session was created
	 * @var string
	 */
	private $sessionKey;

	/**
	 * Timestamp when session was created
	 * @var int
	 */
	private $sessionTimestamp;

	/**
	 * @param API $api
	 */
	function __construct(API $api, $cacheKey) {
		$this->api = $api;
		$this->cacheKey = $cacheKey . ':' . md5($api->getPlatform());
		if (!$this->loadFromCache()) {
			$this->createSession();
		}
	}

	/**
	 * Check to see if our session has expired.
	 *
	 * @return bool
	 */
	public function isExpired() {
		return time() - $this->sessionTimestamp > $this->api->sessionTTL();
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->sessionKey;
	}

	/**
	 * @return int
	 */
	public function getTimestamp() {
		return $this->sessionTimestamp;
	}

	/**
	 * Looks for and loads a valid session from the caching layer, if available
	 * @return bool true if valid session was found
	 */
	private function loadFromCache() {
		if (!$this->api->getCache()) {
			return false;
		}
		$data = $this->api->getCache()->fetch($this->cacheKey);
		if ($data) {
			list($this->sessionKey, $this->sessionTimestamp) = unserialize($data);
			return !$this->isExpired();
		} else {
			return false;
		}
	}

	/**
	 * Save the current session into the caching layer, if available
	 */
	private function saveToCache() {
		if (!$this->api->getCache()) {
			return;
		}
		$data = serialize([$this->sessionKey, $this->sessionTimestamp]);
		// save for 15 minutes
		$this->api->getCache()->save($this->cacheKey, $data, $this->api->sessionTTL());
	}

	/**
	 * Perform a create session call to the Paladins API.
	 */
	private function createSession() {
		$request = new Request($this->api, 'createsession');
		$body = $request->sendForSession();
		$this->sessionKey = $body->session_id;
		if (empty($this->sessionKey)) {
			throw new ApiException('Bad session returned from API: "'.$body->ret_msg.'" via request: '.$request->getRequestedUrl());
		}
		$this->sessionTimestamp = (int)$request->getTimestamp()->format('U');
		$this->saveToCache();
	}
}
