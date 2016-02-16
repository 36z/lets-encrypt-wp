<?php

namespace LEWP\Request;

class AuthorizationBase extends Request {
	/**
	 * Construct the object.
	 *
	 * Authorize a new FQDN in order to issue certificates for the entity.
	 *
	 * @param  string $url The REST resource URL.
	 * @param  string|\LEWP\NonceCollector $nonce_collector NonceCollector object with nonces.
	 *
	 * @return Registration
	 */
	public function __construct( $args ) {
		parent::__construct( $args );
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