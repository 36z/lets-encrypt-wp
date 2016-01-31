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

//$baseUrl = 'https://lewp.tollmanz.com';
$baseUrl = 'https://acme-staging.api.letsencrypt.org';

echo '<pre>';

echo "\n\n###### Directory\n\n";

$nonceRequest = new \LEWP\Request\Nonce( $baseUrl . '/directory' );
$nonceRequest->send();

$nonce = $nonceRequest->get_response_nonce();

print_r(\wp_remote_retrieve_headers($nonceRequest->get_response()));
print_r(\wp_remote_retrieve_body($nonceRequest->get_response()));

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

echo "\n\n###### New Reg\n\n";

print_r(\wp_remote_retrieve_headers($registrationRequest->get_response()) );
print_r(\wp_remote_retrieve_body($nonceRequest->get_response()));

$nonce = $registrationRequest->get_response_nonce();
$termsUrl = $registrationRequest->get_response()['headers']['location'];

$termsRequest = new \LEWP\Request\TermsOfService( $termsUrl, $nonce, array(
	'resource'  => 'reg',
	'agreement' => 'https://letsencrypt.org/documents/LE-SA-v1.0.1-July-27-2015.pdf'
) );

$termsRequest->sign( $keyPair, 'foo' );
$termsRequest->send();

echo "\n\n###### TOS\n\n";

print_r(\wp_remote_retrieve_headers($termsRequest->get_response()) );
print_r(\wp_remote_retrieve_body($termsRequest->get_response()));

$nonce = $termsRequest->get_response_nonce();

$authorizationRequest = new \LEWP\Request\Authorization( $baseUrl . '/acme/new-authz', $nonce, [
	'resource'   => 'new-authz',
	'identifier' => [
		'type'  => 'dns',
		'value' => 'www.tollmanz.com'
	]
] );

$authorizationRequest->sign( $keyPair, 'foo' );
$authorizationRequest->send();

$headers = \wp_remote_retrieve_headers($authorizationRequest->get_response());

echo "\n\n###### New Authz\n\n";

print_r($headers);
print_r(\wp_remote_retrieve_body($authorizationRequest->get_response()));

$nonce = $authorizationRequest->get_response_nonce();
$challenges = json_decode(\wp_remote_retrieve_body($authorizationRequest->get_response()))->challenges;
$combinations = json_decode(\wp_remote_retrieve_body($authorizationRequest->get_response()))->combinations;
$authorizationCheckUrl = $headers['location'];

print_r($challenges);
print_r($combinations);

$challengeNumber = -1;

foreach ( $challenges as $key => $challenge ) {
	if ( $challenge->type === 'http-01' ) {
		$challengeNumber = $key;
	}
}

$challengeRequest = new \LEWP\Request\Challenge($challenges[ $challengeNumber ]->uri, $nonce, [
	'resource'         => 'challenge',
	'type'             => 'http-01',
	'keyAuthorization' => $challenges[ $challengeNumber ]->token . '.' . $keyPair->thumbprint(),
] );

update_option( 'acme-challenge', $challenges[ $challengeNumber ]->token, 'no' );
update_option( 'acme-keyAuthorization', $challenges[ $challengeNumber ]->token . '.' . $keyPair->thumbprint(), 'no' );

echo "\n\n###### Challenge\n\n";

sleep(60);

$challengeRequest->sign( $keyPair, 'foo' );
$challengeRequest->send();

sleep(1);

for ($i = 0; $i < 1; $i++) {

	$results = \wp_remote_get( $authorizationCheckUrl );

	print_r(\wp_remote_retrieve_body($results) );
	print_r(\wp_remote_retrieve_headers($results) );

	sleep(1);
}

echo '</pre>';
exit();