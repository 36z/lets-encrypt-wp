<?php

namespace LEWP\Request;

class Challenge extends Request {
	/**
	 * Construct the object.
	 *
	 * Request to select the challenge for authorization.
	 *
	 * @param  string $url The REST resource URL.
	 * @param  string|\LEWP\NonceCollector $nonce_collector NonceCollector object with nonces.
	 *
	 * @return Registration
	 */
	public function __construct( $resource, $challenge_uri, $type, $key_authorization, \LEWP\NonceCollector $nonce_collector, \LEWP\Encoder $encoder ) {
		parent::__construct( array(
			'url'             => $challenge_uri,
			'nonce_collector' => $nonce_collector,
			'encoder'         => $encoder,
			'method'          => 'POST',
			'payload'         => array(
				'resource'         => $resource,
				'type'             => $type,
				'keyAuthorization' => $key_authorization,
			),
		) );
	}
}