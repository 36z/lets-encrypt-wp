<?php

class DirectoryTest extends PHPUnit_Framework_TestCase {
	public function test_directory_resource_url_is_set() {
		$url = 'https://acme.example.org';
		$directory = new \LEWP\Request\Directory( $url );

		$this->assertEquals( 'https://acme.example.org', $directory->get_resource() );
		$this->assertEquals( 'https://acme.example.org/directory', $directory->get_url() );
	}

	public function test_send_generates_response_and_sets_properties() {
		$url  = 'https://acme.example.org';
		$args = array(
		);

		$response = MockData::get_directory_response();

		\WP_Mock::setUp();

		// Mock the remote request
		\WP_Mock::wpFunction( 'wp_remote_request', array(
			'args'   => array(
				$url . '/directory',
				$args,
			),
			'times'  => 1,
			'return' => $response,
		) );

		$directory = new \LEWP\Request\Directory( $url );
		$this->assertEquals( $response, $directory->send() );
		$this->assertEquals( $response, $directory->get_response() );
		$this->assertEquals( $response['body'], \json_encode( $directory->get_response_body(), JSON_UNESCAPED_SLASHES ) );
		$this->assertEquals( $response['headers']['replay-nonce'], $directory->get_response_nonce() );

		\WP_Mock::tearDown();
	}
}