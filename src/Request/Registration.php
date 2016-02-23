<?php

namespace LEWP\Request;

use LEWP\LinkParser;

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

	public function parse_response( $body, $headers ) {
		$skeleton = [
			'contact'        => [],
			'agreement'      => '',
			'authorizations' => '',
			'certificates'   => '',
			'extra'          => [
				'terms-of-service' => '',
			]
		];

		if ( ! empty( $body['contact'] ) ) {
			$skeleton['contact'] = $body['contact'];
		}

		if ( ! empty( $headers['link'] ) ) {
			$link_parser = new LinkParser( $headers['link'] );
			$skeleton['extra']['terms-of-service'] = $link_parser->get_header_link( 'terms-of-service' );
		}

		$this->object = $skeleton;
		return $this->object;
	}
}