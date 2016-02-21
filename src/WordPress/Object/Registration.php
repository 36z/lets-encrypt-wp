<?php

namespace LEWP\WordPress\Object;

class Registration extends Object {
	private $id = '';
	protected $content_type = 'lewp-registration';

	public function __construct( $id ) {
		$this->id = $id;
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
		$directory_request = new \LEWP\Request\Registration( $this->directory_uri );
		$directory_request->send();

		$this->save( $this->directory_uri, $directory_request->get_response_body() );

		return $directory_request->get_response_body();
	}
}