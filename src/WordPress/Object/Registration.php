<?php

namespace LEWP\WordPress\Object;

use LEWP\Keys\KeyPair;

class Registration extends Object {
	private $id = '';
	private $email = '';

	/**
	 * @var KeyPair
	 */
	private $key;
	private $key_pass = '';
	protected $object_type = 'lewp-registration';

	public function __construct( $id, $email, $key, $key_pass, $resources, $nonce_collector, $encoder ) {
		$this->id       = $id;
		$this->email    = $email;
		$this->key      = $key;
		$this->key_pass = $key_pass;
		parent::__construct( $resources, $nonce_collector, $encoder );
	}

	public function populate() {
		// Attempt to get from database
		$directory_data = $this->get( $this->id );

		if ( empty( $directory_data ) ) {
			return $this->generate();
		} else {
			return $directory_data;
		}
	}

	private function generate() {
		$registration_request = new \LEWP\Request\Registration( 'new-reg', $this->email, $this->resources, $this->nonce_collector, $this->encoder );

		$registration_request->sign( $this->key, $this->key_pass );
		$registration_request->send( true );

		$object = $registration_request->parse_response( $registration_request->get_response_body(), $registration_request->get_response_headers() );

		$this->save( $this->id, $object );

		return $object;
	}
}