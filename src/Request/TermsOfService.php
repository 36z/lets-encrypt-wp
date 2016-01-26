<?php

namespace LEWP\Request;

class TermsOfService extends Request {
	/**
	 * Construct the nonce.
	 *
	 * @param  string    $resource    The REST URL to get the nonce from.
	 * @param  string    $nonce       The nonce used for the request.
	 * @return Nonce
	 */
	public function __construct( $resource, $nonce = '', $payload = array() ) {
		$this->set_type( 'reg' );
		parent::__construct( $resource, 'POST', $payload, array(), $nonce );
	}
}