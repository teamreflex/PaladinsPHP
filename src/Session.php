<?php
namespace Smite;

class Session {
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
	function __construct(API $api) {
		$this->api = $api;
		$this->createSession();
	}

	/**
	 * Check to see if our session has expired.
	 *
	 * @return bool
	 */
	public function isExpired() {
		return time() - $this->sessionTimestamp > 900;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->sessionKey;
	}

	/**
	 * @return string
	 */
	public function getTimestamp() {
		return $this->sessionTimestamp;
	}

	/**
	 * Perform a create session call to the Smite API.
	 */
	private function createSession() {
		$request = new Request($this->api, 'createsession');
		$body = $request->send();
		$this->sessionKey = $body->session_id;
		if (empty($this->sessionKey)) {
			throw new ApiException('Bad session returned from API: "'.$body->ret_msg.'" via request: '.$request->getRequestedUrl());
		}
		$this->sessionTimestamp = (int)$request->getTimestamp()->format('U');
	}
}
