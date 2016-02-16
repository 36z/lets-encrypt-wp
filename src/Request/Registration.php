<?php

namespace LEWP\Request;

class Registration extends Request {
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
	public function __construct( $resource, $email, \LEWP\Resources\Resources $resources_object, \LEWP\NonceCollector $nonce_collector, \LEWP\Encoder $encoder ) {
		$url = $resources_object->find_resource( $resource );

		parent::__construct( array(
			'url'             => $url,
			'nonce_collector' => $nonce_collector,
			'encoder'         => $encoder,
			'method'          => 'POST',
			'payload'         => array(
				'resource' => $resource,
				'contact'  => array(
					'mailto:' . $email
				)
			),
		) );
	}
}