<?php

namespace LEWP\Request;

class TermsOfService extends Request {
	/**
	 * Construct the object.
	 *
	 * This request object registers a new account.
	 *
	 * @param  string $url The REST resource URL.
	 * @param  string|\LEWP\NonceCollector $nonce_collector NonceCollector object with nonces.
	 *
	 * @return Registration
	 */
	public function __construct( $resource, $url, $terms_of_service_url, \LEWP\NonceCollector $nonce_collector, \LEWP\Encoder $encoder ) {
		parent::__construct( array(
			'url'             => $url,
			'nonce_collector' => $nonce_collector,
			'encoder'         => $encoder,
			'method'          => 'POST',
			'payload'         => array(
				'resource'  => 'reg',
				'agreement' => $terms_of_service_url
			),
		) );
	}
}