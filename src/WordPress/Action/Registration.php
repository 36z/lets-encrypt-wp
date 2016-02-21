<?php

namespace LEWP\WordPress\Action;

class Registration {

	public function __construct( $di_container ) {

	}

	public function create( $contact_details ) {
		$registration = new \LEWP\Request\Registration( 'new-reg', $email, $resources, $nonce_collector, $encoder );
		$registration->sign( $auth_key_object, $auth_key_passphrase );
		$registration->send( true );
	}

	public function get_status() {

	}

	public function load() {

	}

	private function prepare_contact_details( $contact_details ) {
		return array();
	}
}