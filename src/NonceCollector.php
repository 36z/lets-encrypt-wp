<?php

namespace LEWP;

/**
 * Class NonceCollector.
 *
 * The NonceCollector stores nonces from request responses, providing a simple interface for holding and using nonces
 * throughout the life of a request cycle.
 *
 * @package LEWP
 */
class NonceCollector {
	/**
	 * Collection of nonces.
	 *
	 * When a request is made, the replay-nonce header values is added to this array. It is then plucked off when the
	 * next request is made.
	 *
	 * @var array    The nonces collected from the requests.
	 */
	private $nonces = [];

	/**
	 * The index of the current nonce.
	 *
	 * @var int    The index of the current nonce.
	 */
	private $index = 0;

	/**
	 * NonceCollector constructor.
	 *
	 * Instantiate the NonceCollector object. Optional send an initial nonce to get things started.
	 *
	 * @param  string    $nonce    The first nonce in the object.
	 * @return NonceCollector
	 */
	public function __construct( $nonce = '' ) {
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
	public function get_next_nonce() {
		$index  = $this->get_index();
		$nonces = $this->get_nonces();

		$nonce = '';

		if ( isset( $nonces[ $index ] ) ) {
			$nonce = $nonces[ $index ];
			$this->increment_index();
		}

		return $nonce;
	}

	/**
	 * Move the nonce index ahead by one.
	 *
	 * @return void
	 */
	public function increment_index() {
		$index = $this->get_index();
		$index++;

		$this->set_index( $index );
	}

	/**
	 * Add a nonce to the array.
	 *
	 * @param  string    $nonce    The nonce to add.
	 * @return void
	 */
	public function add_nonce( $nonce ) {
		$nonces = $this->get_nonces();
		array_push( $nonces, $nonce );

		$this->set_nonces( $nonces );
	}

	/**
	 * Return the nonces.
	 *
	 * @return array    The collected nonces.
	 */
	public function get_nonces() {
		return $this->nonces;
	}

	/**
	 * Set the array of nonces.
	 *
	 * @param  array    $nonces    The array of nonces.
	 * @return void
	 */
	public function set_nonces( $nonces ) {
		$this->nonces = $nonces;
	}

	/**
	 * Return the current index for the nonce array.
	 *
	 * @return int    The current nonce index.
	 */
	public function get_index() {
		return $this->index;
	}

	/**
	 * Set the index.
	 *
	 * @param  int     $index    The index to set.
	 * @return void
	 */
	public function set_index( $index ) {
		$this->index = $index;
	}
}