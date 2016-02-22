<?php

namespace LEWP\WordPress\Object;

class Registration extends Object {
	private $id = '';
	private $email = '';
	protected $content_type = 'lewp-registration';

	public function __construct( $id, $email, $resources, $nonce_collector, $encoder ) {
		$this->id = $id;
		$this->email = $email;
		parent::__construct( $resources, $nonce_collector, $encoder );
	}

	public function populate() {
		// Attempt to get from database
		$directory_data = $this->get( $this->id );

		var_dump( $directory_data );

		if ( empty( $directory_data ) ) {
			return $this->generate();
		} else {
			return $directory_data;
		}
	}

	private function generate() {
		$directory_request = new \LEWP\Request\Registration( 'new-reg', $this->email, $this->resources, $this->nonce_collector, $this->encoder );
		$directory_request->send();

		$this->save( $this->id, $directory_request->get_response_body() );

		var_dump( $directory_request->get_response_body() );

		return $directory_request->get_response_body();
	}
}