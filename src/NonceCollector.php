<?php

namespace LEWP;
use LEWP\Request\Directory;

/**
 * Class NonceCollector.
 *
 * The NonceCollector stores nonces from request responses, providing a simple interface for holding and using nonces
 * throughout the life of a request cycle.
 *
 * @package LEWP
 */
class NonceCollector {
	private $nonce_key = 'lewp-nonces';

	/**
	 * NonceCollector constructor.
	 *
	 * Instantiate the NonceCollector object. Optional send an initial nonce to get things started.
	 *
	 * @param  string    $directory_uri    The URI for the resource the nonce is being retrieved from.
	 * @param  string    $nonce            The first nonce in the object.
	 * @return NonceCollector
	 */
	public function __construct( $directory_uri, $nonce = '' ) {
		$this->directory_uri = $directory_uri;
		$this->host          = parse_url( $directory_uri, PHP_URL_HOST );

		if ( ! empty( $nonce ) ) {
			$this->add_nonce( $nonce );
		}
	}

	/**
	 * Get the current nonce.
	 *
	 * Pluck the most recent unused nonce off the array.
	 *
	 * @return string    A replay nonce.
	 */
	public function get_nonce() {
		$nonces = $this->retrieve_nonces();

		if ( isset( $nonces[ $this->host ][0] ) ) {
			$nonce = $nonces[ $this->host ][0];
			$this->remove_nonce( $nonce );
		} else {
			$directory_request = new Directory( $this->directory_uri );
			$directory_request->send();

			$nonce = $directory_request->get_response_headers()['replay-nonce'];
		}

		return $nonce;
	}

	/**
	 * Add a nonce to the array.
	 *
	 * @param  string    $nonce            The nonce to add.
	 * @return void
	 */
	public function add_nonce( $nonce ) {
		$nonces = $this->retrieve_nonces();

		if ( ! isset( $nonces[ $this->host ] ) ) {
			$nonces[ $this->host ] = array();
		}

		array_push( $nonces[ $this->host ], $nonce );

		$this->update_nonces( $nonces );
	}

	public function remove_nonce( $nonce ) {
		$nonces = $this->retrieve_nonces();

		if ( ! empty( $nonces[ $this->host ] ) ) {
			$key = array_search( $nonce, $nonces[ $this->host ] );

			if ( false !== $key ) {
				unset( $nonces[ $this->host ][ $key ] );
			}
		}
	}

	public function retrieve_nonces() {
		return \get_option( $this->nonce_key, array() );
	}

	public function update_nonces( $nonces ) {
		return \update_option( $this->nonce_key, $nonces, 'no' );
	}
}