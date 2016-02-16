<?php

namespace LEWP\Request;

class AuthorizationStatus extends AuthorizationBase {
	/**
	 * Construct the object.
	 *
	 * Primary purpose of this method is to set the ACME resource type.
	 *
	 * @param  string $url The REST resource URL.
	 * @param  string|\LEWP\NonceCollector $nonce_collector NonceCollector object with nonces.
	 *
	 * @return Directory
	 */
	public function __construct( $url, $nonce_collector = '' ) {
		parent::__construct( array(
			'url'             => $url,
			'nonce_collector' => $nonce_collector,
			'method'          => 'GET'
		) );
	}

	/**
	 * Check if the response includes a valid challenge.
	 *
	 * @return bool True is challenge is valid; false if any other status.
	 */
	public function is_valid() {
		$response_body = $this->get_response_body();
		return ( isset( $response_body['status'] ) && 'valid' === $response_body['status'] );
	}
}