<?php

namespace LEWP\Commands\Cert;
use LEWP\Encoder;
use LEWP\Keys\KeyPair;
use LEWP\Keys\Storage\Option;
use LEWP\LinkParser;
use LEWP\NonceCollector;
use LEWP\Request\Authorization;
use LEWP\Request\AuthorizationStatus;
use LEWP\Request\Challenge;
use LEWP\Request\Directory;
use LEWP\Request\Registration;
use LEWP\Request\TermsOfService;
use LEWP\Resources\Resources;
use WP_CLI;
use WP_CLI_Command;
use \LEWP\Commands\Utils;

/**
 * Implements example command.
 */
class Cert extends WP_CLI_Command {

	/**
	 * Prints a greeting.
	 *
	 * ## OPTIONS
	 *
	 * <directory-uri>
	 * : The directory URI for the CA's ACME server.
	 *
	 * <domain>
	 * : The domain to issue a certificate for.
	 *
	 * <auth-key>
	 * : ID for stored key, path to key, or DER encoded key.
	 *
	 * <auth-key-passphrase>
	 * : The passphrase used to encrypt the auth key.
	 *
	 * <email>
	 * : The email address for the registration request.
	 *
	 * <auto-accept-tos>
	 * : Indicate that you automatically accept the terms of service. 'yes' or 'no' (default).
	 *
	 * <challenge-type>
	 * : The type of challenge to use.
	 *
	 * <debug-level>
	 * : Specify the level of debug information to display.
	 *
	 * ## EXAMPLES
	 *
	 *     wp example hello Newman
	 *
	 * @synopsis --directory-uri=<directory-uri> --domain=<domain> --auth-key=<auth-key> --auth-key-passphrase=<auth-key-passphrase> --email=<email> [--auto-accept-tos=<auto-accept-tos>] [--challenge-type=<challenge-type>] [--debug-level=<debug>]
	 * @subcommand new
	 */
	public function _new( $args, $assoc_args ) {
		$directory_uri       = '';
		$domain              = '';
		$auth_key_passphrase = '';
		$auth_key            = '';
		$email               = '';
		$auto_accept_tos     = false;
		$challenge_type      = 'http-01';
		$debug               = 'v';

		// Prepare a key storage object to handle keys
		$key_storage = Option::get_instance();

		if ( ! empty( $assoc_args['directory-uri'] ) ) {
			$directory_uri = $assoc_args['directory-uri'];
		}

		if ( ! empty( $assoc_args['domain'] ) ) {
			$domain = $assoc_args['domain'];
		}

		if ( ! empty( $assoc_args['auth-key-passphrase'] ) ) {
			$auth_key_passphrase = $assoc_args['auth-key-passphrase'];
		}

		if ( ! empty( $assoc_args['auth-key'] ) ) {
			$auth_key = $assoc_args['auth-key'];

			// @todo: Figure out what the key is and properly import it for use; right now, it only supports a stored key
			$auth_key_object = $key_storage->get( $auth_key );
			$auth_key_object->generate( $auth_key_passphrase );
		}

		if ( ! empty( $assoc_args['email'] ) ) {
			$email = $assoc_args['email'];
		}

		if ( ! empty( $assoc_args['debug-level'] ) ) {
			$debug = $assoc_args['debug-level'];
		}

		if ( ! empty( $assoc_args['auto-accept-tos'] ) ) {
			$auto_accept_tos = ( 'yes' === $assoc_args['auto-accept-tos'] );
		}

		if ( ! empty( $assoc_args['challeng-type'] ) ) {
			$auto_accept_tos = $assoc_args['challeng-type'];
		}

		// Kick things off by getting the directory information and storing it for later use
		Utils::display_debug_message( $debug, 'Discovering ACME resources' );

		// @todo: we need to cache either the directory request or the resulting resources object. It does not make sense that these resources will change often.

		$directory_request = new Directory( $directory_uri );
		$directory_request->send();

		// Validate and store the resources
		$resources = new Resources( $directory_request->get_response_body() );
		Utils::display_debug_message( $debug, '', 'resources discovered', $resources->get_resource_urls() );

		// Initialize our nonce collector that tracks nonces throughout the nonce flow
		$nonce_collector = new NonceCollector( $directory_request->get_response_nonce() );

		// Prepare the encoder object to pass around
		$encoder = new Encoder();

		// Generate a new auth key if needed
		if ( empty ( $auth_key_object ) ) {
			$auth_key_object = new KeyPair( $auth_key );
			$auth_key_object->generate( $auth_key_passphrase );

			// Store the key for future use
			$option_storage = Option::get_instance();
			$option_storage->save( $auth_key_object );

			Utils::display_line( 'Generated key' );
		}

		// Register the new account
		$registration = new Registration( 'new-reg', $email, $resources, $nonce_collector, $encoder );
		$registration->sign( $auth_key_object, $auth_key_passphrase );

		Utils::display_debug_message( $debug, 'Sending registration' );
		$registration->send( true );

		// Process response: 201 - registration created; 409 - registration exists
		if ( 409 === \wp_remote_retrieve_response_code( $registration->get_response() ) ) {
			Utils::display_debug_message( $debug, '', 'account already registered', $registration->get_response_body()['detail'] );
		} else if ( 201 === \wp_remote_retrieve_response_code( $registration->get_response() ) ) {
			Utils::display_debug_message( $debug, 'Account registered' );
		} else {
			// @todo: handle error when the registration fails
		}

		// Get the terms of service URL from the registration request
		if ( isset( $registration->get_response_headers()['link'] ) ) {
			$link_parser = new LinkParser( $registration->get_response_headers()['link'] );
		} else {
			// @todo: if link isn't available, we need to do something about it
		}

		$terms_link = ( isset( $link_parser ) ) ? $link_parser->get_header_link( 'terms-of-service' ) : '';
		$terms_url  = ( ! empty( $registration->get_response_headers()['location'] ) ) ? $registration->get_response_headers()['location'] : '';

		if ( ! empty( $terms_link ) ) {
			Utils::display_debug_message( $debug, 'Terms of Service URL discovered', $terms_link );
		} else {
			Utils::display_debug_message( $debug, 'Terms of Service URL not discovered', '', $registration->get_response_headers() );
		}

		if ( true === $auto_accept_tos ) {
			Utils::display_debug_message( $debug, '', 'terms of service automatically accepted' );
		} else {
			Utils::display_confirm( 'Do you accept the terms of service (' . $terms_link . ')?' );
			Utils::display_debug_message( $debug, '', 'terms of service accepted by user' );
		}

		// Let the ACME server know that the TOS has been accepted
		$terms_of_service_request = new TermsOfService( 'reg', $terms_url, $terms_link, $nonce_collector, $encoder );
		$terms_of_service_request->sign( $auth_key_object, $auth_key_passphrase );
		$terms_of_service_request->send( true );

		if ( 202 === \wp_remote_retrieve_response_code( $terms_of_service_request->get_response() ) ) {
			Utils::display_debug_message( $debug, '', 'terms of service acceptance acknowledged by ACME server' );
		} else {
			// @todo: fail state for TOS request being rejected
		}

		// Make a request to authorize a new FQDN for certificate issuance
		$authorization_request = new Authorization( 'new-authz', $domain, $resources, $nonce_collector, $encoder );
		$authorization_request->sign( $auth_key_object, $auth_key_passphrase );

		Utils::display_debug_message( $debug, 'Sending authorization request' );
		$authorization_request->send( true );

		// Pull the challenges from the authorization request response
		$challenges = $authorization_request->get_response_body()['challenges'];

		// Find an http-01 challenge
		foreach ( $challenges as $key => $challenge ) {
			if ( $challenge['type'] === $challenge_type ) {
				$challenge_number = $key;
			}
		}

		$challenge = ( isset( $challenge_number ) && isset( $challenges[ $challenge_number ] ) ) ? $challenges[ $challenge_number ] : '';

		if ( ! empty( $challenge ) ) {
			Utils::display_debug_message( $debug, 'Challenge selected', '', $challenge );
		}

		// @todo: set up the WP URL to respond to the challenge
		// Currently, if testing locally, you have to handle this piece manually by setting up the challenge URL
		// using the token and key authorization displayed below.

		// Let the ACME server know about the challenge we are accepting
		$key_authorization = $challenge['token'] . '.' . $auth_key_object->thumbprint();
		$challenge_request = new Challenge( 'challenge', $challenge['uri'], $challenge['type'], $key_authorization, $nonce_collector, $encoder );
		$challenge_request->sign( $auth_key_object, $auth_key_passphrase );

		Utils::display_debug_message( $debug, 'Responding to challenge', array( 'key-authorization' => $key_authorization ) );

		WP_CLI::line( $challenge['token'] );
		WP_CLI::line( $key_authorization );

		WP_CLI::confirm( 'Is the challenge ready?' );

		$challenge_request->send( true );

		sleep(5);

		$authorization_status_request = new AuthorizationStatus( $challenge['uri'], $nonce_collector );
		$authorization_status_request->send();
		var_dump( $authorization_status_request->get_response_body(), $authorization_status_request->get_response()['response'] );
	}

	/**
	 * Returns the list of resources associated with a CA's ACME implementation.
	 *
	 * ## OPTIONS
	 *
	 * <uri>
	 * : The base URI for the ACME server.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cert directory --uri=https://acme-staging.api.letsencrypt.org/directory
	 *
	 * @synopsis --uri=<uri>
	 *
	 * @since 1.0.0.
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function directory( $args, $assoc_args ) {
		if ( ! empty( $assoc_args['uri'] ) ) {
			$uri = $assoc_args['uri'];

			// Make the request to the directory URI
			$directory_request = new Directory( $uri );
			$directory_request->send();

			Utils::display_success( json_encode( $directory_request->get_response_body() ) );
		} else {
			Utils::display_error( 'command requires a `--uri` argument' );
		}
	}

	public function registration( $args, $assoc_args ) {}

	public function terms_of_service( $args, $assoc_args ) {}

	public function authorization( $args, $assoc_args ) {}

	public function challenge( $args, $assoc_args ) {}

	public function certificate( $args, $assoc_args ) {}
}

WP_CLI::add_command( 'cert', '\LEWP\Commands\Cert\Cert' );