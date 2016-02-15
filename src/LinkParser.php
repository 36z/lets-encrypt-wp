<?php

namespace LEWP;

class LinkParser {
	/**
	 * The raw headers array submitted to the class.
	 *
	 * @var array The raw link data passed to the class.
	 */
	private $header_links_raw = array();

	/**
	 * The parsed and cleaned links.
	 *
	 * @var array The processed link headers.
	 */
	private $header_links = array();

	/**
	 * Constructs the class.
	 *
	 * Headers are automatically parsed when the class is constructed.
	 *
	 * @param array $header_links_raw The raw link headers.
	 * @return \LEWP\LinkParser
	 */
	public function __construct( $header_links_raw ) {
		$this->header_links_raw = $header_links_raw;
		$this->parse_header_links( $header_links_raw );
	}

	/**
	 * Parses the headers into an easy to use array.
	 *
	 * @param array $header_links_raw The raw link headers.
	 * @return array Array of headers with "rel" values as keys and links as values.
	 */
	public function parse_header_links( $header_links_raw ) {
		$links = array();

		foreach ( $header_links_raw as $header ) {
			$link  = $this->parse_header_link( $header );
			$links = array_merge( $links, $link );
		}

		$this->set_header_links( $links );
		return $this->get_header_links();
	}

	/**
	 * Parses an individual link header.
	 *
	 * @param string $header The header to parse.
	 * @return array The parsed header.
	 */
	public function parse_header_link( $header ) {
		$key  = '';
		$link = '';

		$pieces = explode( '>;rel="', $header );

		if ( 2 === count( $pieces ) ) {
			$link = ltrim( $pieces[0], '<' );
			$key  = rtrim( $pieces[1], '"' );
		}

		return array(
			$key => $link
		);
	}

	/**
	 * Get an individual header link by rel value.
	 *
	 * @param string $name The rel value to get.
	 * @return string The link value.
	 */
	public function get_header_link( $name ) {
		$links = $this->get_header_links();
		$link  = '';

		if ( isset( $links[ $name ] ) ) {
			$link = $links[ $name ];
		}

		return $link;
	}

	/**
	 * Set the headers.
	 *
	 * @param array $header_links The headers to set.
	 * @return void
	 */
	public function set_header_links( $header_links ) {
		$this->header_links = $header_links;
	}

	/**
	 * Get the parsed headers.
	 *
	 * @return array The parsed headers.
	 */
	public function get_header_links() {
		return $this->header_links;
	}
}