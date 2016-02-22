<?php

namespace LEWP\WordPress\Object;

abstract class Object {
	protected $post_id = 0;

	public function __construct( $resources, $nonce_collector, $encoder ) {
		$this->resources       = $resources;
		$this->nonce_collector = $nonce_collector;
		$this->encoder         = $encoder;
	}

	public function save( $identifier, $data ) {
		$option = json_decode( \get_option( $this->content_type, '{}' ), true );
		$option[ $identifier ] = $data;

		return \update_option( $this->content_type, json_encode( $option ) );
	}

	public function get( $identifier ) {
		$option = json_decode( \get_option( $this->content_type, '{}' ), true );
		$data = array();

		if ( isset( $option[ $identifier ] ) ) {
			$data = $option[ $identifier ];
		}

		return $data;
	}

	public function state() {}
}