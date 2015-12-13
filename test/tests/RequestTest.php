<?php

class RequestTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		\WP_Mock::setUp();
	}

	public function tearDown() {
		\WP_Mock::tearDown();
	}

	public function test_request_signing() {
		$request = new StubRequest( 'https://acme.example.org' );

		$body = [
			'hello' => 'world',
		];
		$request->set_request_body( $body );
		$request->set_request_nonce( 'nonce' );

		$keypair = new \LEWP\Keys\KeyPair( 'test' );
		$keypair->generate( 'foo' );

		$request->sign( $keypair, 'foo' );

		$args = [
			'body'    => json_encode( $request->get_signature(), JSON_UNESCAPED_SLASHES ),
			'headers' => $request->get_request_headers(),
		];

		// Mock the remote request
		\WP_Mock::wpFunction( 'wp_remote_request', array(
			'args'   => [
				'https://acme.example.org/acme/stub',
				$args,
			],
			'times'  => 1,
			'return' => [], // of no concern
		) );

		$request->send();

		$request_args = $request->get_request_args();
		$request_body = json_decode( $request_args['body'] );

		$this->assertObjectHasAttribute( 'header',    $request_body );
		$this->assertObjectHasAttribute( 'protected', $request_body );
		$this->assertObjectHasAttribute( 'payload',   $request_body );
		$this->assertObjectHasAttribute( 'signature', $request_body );

	}

}

class StubRequest extends LEWP\Request\Request {
	/**
	 * @param string $resource The REST resource URL.
	 */
	public function __construct( $resource ) {
		parent::__construct( $resource );
		$this->set_type( 'stub' );
	}

}
