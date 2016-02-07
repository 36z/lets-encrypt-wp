<?php

class RequestTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		\WP_Mock::setUp();
	}

	public function tearDown() {
		\WP_Mock::tearDown();
	}

	public function test_request_signing() {
		$url = 'https://acme.example.org/acme/stub';
		$body = [
			'hello' => 'world',
		];

		$request = new StubRequest( $url, $body );

		$request->set_request_nonce( 'nonce' );

		$keypair = new \LEWP\Keys\KeyPair( 'test' );
		$keypair->generate( 'foo' );

		$request->sign( $keypair, 'foo' );

		$request_body = $request->get_signature();

		$this->assertArrayHasKey( 'header',    $request_body );
		$this->assertArrayHasKey( 'protected', $request_body );
		$this->assertArrayHasKey( 'payload',   $request_body );
		$this->assertArrayHasKey( 'signature', $request_body );
	}
}

class StubRequest extends LEWP\Request\Request {
	/**
	 * @param string $resource The REST resource URL.
	 */
	public function __construct( $url, $payload ) {
		parent::__construct( array( 'url' => $url, 'payload' => $payload, 'encoder' => new \LEWP\Encoder() ) );
		$this->set_type( 'stub' );
	}

}
