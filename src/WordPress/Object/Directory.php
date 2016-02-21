<?php

namespace LEWP\WordPress\Object;

class Directory extends Object {
	private $directory_uri = '';
	protected $content_type = 'lewp-directory';

	public function __construct( $directory_uri ) {
		$this->directory_uri = $directory_uri;
	}

	public function populate() {
		$identifier = $this->directory_uri;

		// Attempt to get from database
		$directory_data = $this->get( $identifier );

		if ( empty( $directory_data ) ) {
			return $this->generate();
		} else {
			return $directory_data;
		}
	}

	private function generate() {
		$directory_request = new \LEWP\Request\Directory( $this->directory_uri );
		$directory_request->send();

		$this->save( $this->directory_uri, $directory_request->get_response_body() );

		return $directory_request->get_response_body();
	}
}