<?php
/**
 * Plugin Name: Let's Encrypt WP
 * Plugin URI: http://wordpress.org/plugins/lets-encrypt-wp/
 * Description: A certificate acquisition and management plugin.
 * Author: Zack Tollman
 * Version: 0.0.1
 * Author URI: https://www.tollmanz.com
 */

namespace LEWP;

require_once __DIR__ . '/vendor/autoload.php';

$baseUrl = 'https://lewp.tollmanz.com';

echo '<pre>';

$nonceRequest = new \LEWP\Request\Nonce( $baseUrl . '/directory' );
$nonceRequest->send();

$nonce = $nonceRequest->get_response_nonce();

print_r( $nonce );

$registrationRequest = new \LEWP\Request\Registration( $baseUrl . '/acme/new-reg', $nonce, array(
	'resource' => 'new-reg',
	'contact'  => array(
		'mailto:tollmanz@gmail.com'
	)
) );

$keyPair = new \LEWP\Keys\KeyPair( 'test' );
$keyPair->generate( 'foo' );

$registrationRequest->sign( $keyPair, 'foo' );
$registrationRequest->send();

print_r( json_decode( $registrationRequest->get_request_args()['body'] ) );
print_r(\wp_remote_retrieve_headers($registrationRequest->get_response()) );

$nonce = $registrationRequest->get_response_nonce();
$termsUrl = $registrationRequest->get_response()['headers']['location'];

print_r($registrationRequest->get_response()['headers']);
print_r(json_decode($registrationRequest->get_response()['body']));

$termsRequest = new \LEWP\Request\TermsOfService( $termsUrl, $nonce, array(
	'resource'  => 'reg',
	'agreement' => 'https://lewp.tollmanz.com/terms/v1'
) );

$termsRequest->sign( $keyPair, 'foo' );
$termsRequest->send();

print_r( json_decode( $termsRequest->get_request_args()['body'] ) );
print_r(\wp_remote_retrieve_headers($termsRequest->get_response()) );

$nonce = $termsRequest->get_response_nonce();

$authorizationRequest = new \LEWP\Request\Authorization( 'https://lewp.tollmanz.com/acme/new-authz', $nonce, [
	'resource'   => 'new-authz',
	'identifier' => [
		'type'  => 'dns',
		'value' => 'tollmanz.com'
	]
] );

$authorizationRequest->sign( $keyPair, 'foo' );
$authorizationRequest->send();

print_r( json_decode( $authorizationRequest->get_request_args()['body'] ) );
print_r(\wp_remote_retrieve_headers($authorizationRequest->get_response()) );
print_r(json_decode(\wp_remote_retrieve_body($authorizationRequest->get_response())) );

$nonce = $authorizationRequest->get_response_nonce();
$challenges = json_decode(\wp_remote_retrieve_body($authorizationRequest->get_response()))->challenges;

$challengeNumber = -1;

foreach ( $challenges as $key => $challenge ) {
	if ( $challenge->type === 'http-01' ) {
		$challengeNumber = $key;
	}
}

$challengeRequest = new \LEWP\Request\Challenge( $challenges[ $challengeNumber ]->uri, $nonce, [
	'resource'         => 'challenge',
//	'type'             => $challenges[ $challengeNumber ]->type,
	'keyAuthorization' => $challenges[ $challengeNumber ]->token . '.' . $keyPair->thumbprint()
] );

$challengeRequest->sign( $keyPair, 'foo' );
$challengeRequest->send();

print_r( json_decode( $challengeRequest->get_request_args()['body'] ) );
print_r(\wp_remote_retrieve_headers($challengeRequest->get_response()) );
print_r(json_decode(\wp_remote_retrieve_body($challengeRequest->get_response())) );

echo '</pre>';
exit();