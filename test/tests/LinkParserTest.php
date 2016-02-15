<?php

namespace LEWP;

class LinkParserTest extends \PHPUnit_Framework_TestCase {
	public function test_link_parser_parses_links() {
		$links = array(
			0 => '<https://acme-staging.api.letsencrypt.org/acme/new-authz>;rel="next"',
			1 => '<https://letsencrypt.org/documents/LE-SA-v1.0.1-July-27-2015.pdf>;rel="terms-of-service"',
		);

		$expected_links = array(
			'next'             => 'https://acme-staging.api.letsencrypt.org/acme/new-authz',
			'terms-of-service' => 'https://letsencrypt.org/documents/LE-SA-v1.0.1-July-27-2015.pdf'
		);

		$parser = new LinkParser( $links );

		$this->assertEquals( $expected_links, $parser->get_header_links() );
		$this->assertEquals( 'https://letsencrypt.org/documents/LE-SA-v1.0.1-July-27-2015.pdf', $parser->get_header_link( 'terms-of-service' ) );
	}
}