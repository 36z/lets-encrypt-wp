<?php

namespace LEWP;

class NonceCollectorTest extends \PHPUnit_Framework_TestCase {

	public function test_nonce_collector_constructs_correctly() {
		$nonceCollector = new NonceCollector();
		$this->assertEquals( 0, count( $nonceCollector->get_nonces() ) );

		$nonce = 'a';
		$nonceCollector = new NonceCollector( 'a' );
		$this->assertEquals( $nonce, $nonceCollector->get_nonces()[0] );
	}

	public function test_nonce_collector_get_next_nonce() {
		$nonce = 'a';
		$nonceCollector = new NonceCollector( $nonce );
		$this->assertEquals( $nonce, $nonceCollector->get_next_nonce() );
		$this->assertEquals( 1, $nonceCollector->get_index() );

		$nonce2 = 'b';
		$nonceCollector->add_nonce( $nonce2 );
		$this->assertEquals( $nonce2, $nonceCollector->get_next_nonce() );
		$this->assertEquals( 2, $nonceCollector->get_index() );
	}
}