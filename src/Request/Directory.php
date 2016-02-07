<?php

namespace LEWP\Request;

class Directory extends Request {
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
}