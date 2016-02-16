<?php

namespace LEWP\Request;

class Authorization extends AuthorizationBase {
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
	public function __construct( $resource, $domain, \LEWP\Resources\Resources $resources_object, \LEWP\NonceCollector $nonce_collector, \LEWP\Encoder $encoder ) {
		$url = $resources_object->find_resource( $resource );

		parent::__construct( array(
			'url'             => $url,
			'nonce_collector' => $nonce_collector,
			'encoder'         => $encoder,
			'method'          => 'POST',
			'payload'         => array(
				'resource'   => $resource,
				'identifier' => [
					'type'  => 'dns',
					'value' => $domain
				]
			),
		) );
	}
}