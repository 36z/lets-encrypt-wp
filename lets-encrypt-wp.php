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

class Base64UrlSafeEncoder
{
	public function encode($data)
	{
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}
	public function decode($data)
	{
		return base64_decode(strtr($data, '-_', '+/'));
	}
}

//$baseUrl = 'https://lewp.tollmanz.com';
$baseUrl = 'https://acme-staging.api.letsencrypt.org';

echo '<pre>';
//
//echo "\n\n###### Directory\n\n";
//
//$nonceRequest = new \LEWP\Request\Nonce( $baseUrl . '/directory' );
//$nonceRequest->send();
//
//$nonce = $nonceRequest->get_response_nonce();
//
//print_r(\wp_remote_retrieve_headers($nonceRequest->get_response()));
//print_r(\wp_remote_retrieve_body($nonceRequest->get_response()));
//
//$registrationRequest = new \LEWP\Request\Registration( $baseUrl . '/acme/new-reg', $nonce, array(
//	'resource' => 'new-reg',
//	'contact'  => array(
//		'mailto:tollmanz@gmail.com'
//	)
//) );
//
//$keyPair = new \LEWP\Keys\KeyPair( 'test' );
//$keyPair->generate( 'foo' );
//
//update_option( 'private-key', $keyPair->get_private_key() );
//
//$registrationRequest->sign( $keyPair, 'foo' );
//$registrationRequest->send();
//
//echo "\n\n###### New Reg\n\n";
//
//print_r(\wp_remote_retrieve_headers($registrationRequest->get_response()) );
//print_r(\wp_remote_retrieve_body($nonceRequest->get_response()));
//
//$nonce = $registrationRequest->get_response_nonce();
//$termsUrl = $registrationRequest->get_response()['headers']['location'];
//
//$termsRequest = new \LEWP\Request\TermsOfService( $termsUrl, $nonce, array(
//	'resource'  => 'reg',
//	'agreement' => 'https://letsencrypt.org/documents/LE-SA-v1.0.1-July-27-2015.pdf'
//) );
//
//$termsRequest->sign( $keyPair, 'foo' );
//$termsRequest->send();
//
//echo "\n\n###### TOS\n\n";
//
//print_r(\wp_remote_retrieve_headers($termsRequest->get_response()) );
//print_r(\wp_remote_retrieve_body($termsRequest->get_response()));
//
//$nonce = $termsRequest->get_response_nonce();
//
//$authorizationRequest = new \LEWP\Request\Authorization( $baseUrl . '/acme/new-authz', $nonce, [
//	'resource'   => 'new-authz',
//	'identifier' => [
//		'type'  => 'dns',
//		'value' => 'www.tollmanz.com'
//	]
//] );
//
//$authorizationRequest->sign( $keyPair, 'foo' );
//$authorizationRequest->send();
//
//$headers = \wp_remote_retrieve_headers($authorizationRequest->get_response());
//
//echo "\n\n###### New Authz\n\n";
//
//print_r($headers);
//print_r(\wp_remote_retrieve_body($authorizationRequest->get_response()));
//
//$nonce = $authorizationRequest->get_response_nonce();
//$challenges = json_decode(\wp_remote_retrieve_body($authorizationRequest->get_response()))->challenges;
//$combinations = json_decode(\wp_remote_retrieve_body($authorizationRequest->get_response()))->combinations;
//$authorizationCheckUrl = $headers['location'];
//
//print_r($challenges);
//print_r($combinations);
//
//$challengeNumber = -1;
//
//foreach ( $challenges as $key => $challenge ) {
//	if ( $challenge->type === 'http-01' ) {
//		$challengeNumber = $key;
//	}
//}
//
//$challengeRequest = new \LEWP\Request\Challenge($challenges[ $challengeNumber ]->uri, $nonce, [
//	'resource'         => 'challenge',
//	'type'             => 'http-01',
//	'keyAuthorization' => $challenges[ $challengeNumber ]->token . '.' . $keyPair->thumbprint(),
//] );
//
//update_option( 'acme-challenge', $challenges[ $challengeNumber ]->token, 'no' );
//update_option( 'acme-keyAuthorization', $challenges[ $challengeNumber ]->token . '.' . $keyPair->thumbprint(), 'no' );
//
//echo "\n\n###### Challenge\n\n";
//
//sleep(60);
//
//$challengeRequest->sign( $keyPair, 'foo' );
//$challengeRequest->send();
//
//sleep(5);
//
//// Note that in the real world, we need to deal with invalid results
//for ($i = 0; $i < 1; $i++) {
//
//	$results = \wp_remote_get( $authorizationCheckUrl );
//
//	print_r(\wp_remote_retrieve_body($results) );
//	print_r(\wp_remote_retrieve_headers($results) );
//
//	sleep(1);
//}
//
//$nonce = $challengeRequest->get_response_nonce();

$nonceRequest = new \LEWP\Request\Nonce( $baseUrl . '/directory' );
$nonceRequest->send();

$nonce = $nonceRequest->get_response_nonce();

$keyPair = new \LEWP\Keys\KeyPair('whatever', get_option('private-key'));
$keyPair->generate('foo');

$domainKeyPair = new \LEWP\Keys\KeyPair( 'domain' );
$domainKeyPair->generate( 'blah' );

$config = array(
	'config' => __DIR__ . '/openssl.cnf',
	'digest_alg' => 'sha256',
	'private_key_type' => OPENSSL_KEYTYPE_RSA,
	'private_key_bits' => 2048 // ---> obviously good
);

$dn = array(
	"commonName" => "www.tollmanz.com",
);

$domainKeyPair->generate_csr( $dn, $config );

$notBeforeDate = new \DateTime();
$notAfterDate = clone $notBeforeDate;

$notBeforeString = $notBeforeDate->format( \DateTime::RFC3339 );

$notAfterDate->modify( '+3 months' );
$notAfterString = $notAfterDate->format( \DateTime::RFC3339 );

$newCertificateRequest = new \LEWP\Request\Certificate( $baseUrl . '/acme/new-cert', $nonce, [
	'resource'  => 'new-cert',
	'csr'       => trim( rtrim( strtr( $domainKeyPair->get_csr_pem($domainKeyPair->get_csr()), '+/', '-_'), '=' ) ),
	'notBefore' => $notBeforeString,
	'notAfter'  => $notAfterString,
] );

$newCertificateRequest->sign( $keyPair, 'foo' );
$newCertificateRequest->send();

print_r(json_encode($newCertificateRequest->get_request_body()) );
print_r(\wp_remote_retrieve_headers($newCertificateRequest->get_response()) );
print_r(\wp_remote_retrieve_body($newCertificateRequest->get_response()));

$pem = chunk_split(base64_encode(wp_remote_retrieve_body($newCertificateRequest->get_response())), 64, "\n");
$pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
var_dump($pem);

echo '</pre>';
exit();