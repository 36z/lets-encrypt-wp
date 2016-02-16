<?php

namespace LEWP\Request;

abstract class Request {
	/**
	 * The response returned from the HTTP request.
	 *
	 * @var array|\WP_Error    Either a WP_Error or an array with response data.
	 */
	protected $response = array();

	/**
	 * The URL for the request.
	 *
	 * @var string    The URL for the request.
	 */
	protected $url = '';

	/**
	 * The array of request arguments.
	 *
	 * @var array The arguments to send in the request.
	 */
	protected $request_args = [];

	/**
	 * The payload sent with the request.
	 *
	 * The payload submitted to the request object is in plaintext; however, the payload will be signed as part of the
	 * JWT when the request is made.
	 *
	 * @var array The payload to send with the request.
	 */
	protected $request_payload = [];

	/**
	 * The body of the request.
	 *
	 * @var string The body of a request.
	 */
	protected $request_body = '';

	/**
	 * The body content from the response.
	 *
	 * @var array The body content received in the response.
	 */
	protected $response_body = [];

	/**
	 * Array of headers for the request.
	 *
	 * @var array Array of headers keyed by their name.
	 */
	protected $request_headers = [];

	/**
	 * Array of headers for the response.
	 *
	 * @var array Array of headers keyed by their name.
	 */
	protected $response_headers = [];

	/**
	 * CSRF token for the request.
	 *
	 * @var string    The nonce to protect the request against CSRF.
	 */
	protected $request_nonce = '';

	/**
	 * A NonceCollector object for managing nonces.
	 *
	 * @var string|\LEWP\NonceCollector    The NonceCollector object for managing nonces.
	 */
	protected $nonce_collector = '';

	/**
	 * An Encoder object for encoding parts of the request.
	 *
	 * @var string|\LEWP\Encoder    The Encoder object for encoding parts of the request.
	 */
	protected $encoder = '';

	/**
	 * CSRF token received from the request.
	 *
	 * @var string    The nonce to protect the next request against CSRF.
	 */
	protected $response_nonce = '';

	/**
	 * The named ACME resource type to fetch.
	 *
	 * @var string    The ACME request type (e.g., new-reg, new-cert).
	 */
	protected $type = '';

	/**
	 * The request method.
	 *
	 * @var string    The method used for the request.
	 */
	protected $method = 'GET';

	/**
	 * The request signature.
	 *
	 * @var string The signature used for the request.
	 */
	protected $signature = '';

	/**
	 * Construct the request object.
	 *
	 * @param  string                         $url                The REST resource URL.
	 * @param  string                         $method             The request method.
	 * @param  array                          $payload            The payload for the request. This payload will be signed.
	 * @param  string|\LEWP\NonceCollector    $nonce_collector    NonceCollector object with nonces.
	 * @return Request
	 */
	public function __construct( $args = array() ) {
		if ( ! empty( $args['url'] ) ) {
			$this->set_url( $args['url'] );
		}

		if ( ! empty( $args['method'] ) ) {
			$this->set_method( $args['method'] );
		}

		if ( ! empty( $args['payload'] ) ) {
			$this->set_request_payload( $args['payload'] );
		}

		if ( ! empty( $args['nonce_collector'] ) ) {
			$this->set_nonce_collector( $args['nonce_collector'] );
			$this->set_request_nonce( $this->nonce_collector->get_next_nonce() );
		}

		if ( ! empty( $args['encoder'] ) ) {
			$this->set_encoder( $args['encoder'] );
		}
	}

	/**
	 * Send the HTTP request.
	 *
	 * @param bool $sign Whether or not to sign the request.
	 * @return array|\WP_Error    An array of response information on success; \WP_Error on failure.
	 */
	public function send( $sign = false ) {
		$this->request_args = [];

		if ( true === $sign ) {
			$signature = $this->get_signature();

			if ( ! empty( $signature ) ) {
				$this->request_args['body'] = json_encode( $signature, JSON_UNESCAPED_SLASHES );
				$this->set_request_body( $this->request_args['body'] );
			}
		}

		$headers = $this->get_request_headers();

		if ( ! empty( $headers ) ) {
			$this->request_args['headers'] = $headers;
		}

		$this->request_args['method'] = $this->get_method();

		$result = \wp_remote_request( $this->get_url(), $this->get_request_args() );

		$this->set_response( $result );

		if ( is_array( $result ) && isset( $result['body'] ) ) {
			$decoded = json_decode( $result['body'], true );

			if ( ! $decoded ) {
				$decoded = array();
			}

			$this->set_response_body( $decoded );

			if ( isset( $result['headers'] ) ) {
				$this->set_response_headers( $result['headers'] );
			}
		}

		if ( isset( $result['headers']['replay-nonce'] ) ) {
			$this->set_response_nonce( $result['headers']['replay-nonce'] );

			// Add the new nonce to the collector
			if ( method_exists( $this->get_nonce_collector(), 'add_nonce' ) ) {
				$this->get_nonce_collector()->add_nonce( $this->get_response_nonce() );
			}
		}

		return $result;
	}

	/**
	 * Sign the request using a passphrase-protected public/private key pair.
	 *
	 * @param  \LEWP\Keys\KeyPair $keypair    A KeyPair object.
	 * @param  string  $passphrase The passphrase for the KeyPair's private key.
	 * @return bool True on success, false on failure.
	 */
	public function sign( \LEWP\Keys\KeyPair $keypair, $passphrase ) {

		$signed = $this->generate_request_signature( $keypair, $passphrase );

		if ( ! $signed ) {
			return false;
		}

		$this->set_signature( $signed );

		return true;

	}

	/**
	 * Generate a signature used to sign the request.
	 *
	 * @param  \LEWP\Keys\KeyPair $keypair    A KeyPair object.
	 * @param  string  $passphrase The passphrase for the KeyPair's private key.
	 * @return string|bool Signature used to sign the request, boolean false if the request can't be signed.
	 */
	protected function generate_request_signature( \LEWP\Keys\KeyPair $keypair, $passphrase ) {

		$nonce = $this->get_request_nonce();

		$private_key = $keypair->export_private_key( $passphrase );

		if ( ! $private_key ) {
			return false;
		}

		$details = openssl_pkey_get_details( $private_key );
		// @TODO: We'll likely want to make the algorithm a parameter for the method

		$alg = 'RS256';

		$protected_header = [
			'alg' => $alg,
			'jwk' => [
				'kty' => 'RSA',
				'n'   => $this->encoder->encode( $details['rsa']['n'] ),
				'e'   => $this->encoder->encode( $details['rsa']['e'] ),
			],
		];

		$protected = $this->encoder->encode( json_encode( [ 'nonce' => $nonce ], JSON_UNESCAPED_SLASHES ) );
		$payload = $this->encoder->encode( json_encode( $this->get_request_payload(), JSON_UNESCAPED_SLASHES ) );

		$signature = NULL;
		openssl_sign( $protected . '.' . $payload, $signature, $private_key, 'SHA256' );

		return [
			'header'    => $protected_header,
			'protected' => $protected,
			'payload'   => $payload,
			'signature' => $this->encoder->encode( $signature ),
		];
	}

	/**
	 * Get the request URL.
	 *
	 * @return string The URL for the request.
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Set the request URL.
	 *
	 * @param  string    $url    The URL for the request.
	 * @return void
	 */
	public function set_url( $url ) {
		$this->url = $url;
	}

	/**
	 * Get the response object from the request.
	 *
	 * @return array|\WP_Error    Either a WP_Error or an array with response data.
	 */
	public function get_response() {
		return $this->response;
	}

	/**
	 * Set the response object from the request.
	 *
	 * @param array|\WP_Error    $response    Either a WP_Error or an array with response data.
	 */
	public function set_response( $response ) {
		$this->response = $response;
	}

	/**
	 * Get the request args.
	 *
	 * @return array The arguments to send in the request.
	 */
	public function get_request_args() {
		return $this->request_args;
	}

	/**
	 * Get the request body.
	 *
	 * @return array The body content to send with the request.
	 */
	public function get_request_payload() {
		return $this->request_payload;
	}

	/**
	 * Set the request body.
	 *
	 * @param array $body    The body content to send with the request.
	 */
	public function set_request_payload( array $body ) {
		$this->request_payload = $body;
	}

	/**
	 * Get the request body.
	 *
	 * @return string The body content sent in the request.
	 */
	public function get_request_body() {
		return $this->request_body;
	}

	/**
	 * Set the request body.
	 *
	 * @param  string    $body    The request body.
	 * @return void
	 */
	public function set_request_body( $body ) {
		$this->request_body = $body;
	}

	/**
	 * Get the encoder.
	 *
	 * @return \LEWP\Encoder    The encoder to use for the request.
	 */
	public function get_encoder() {
		return $this->encoder;
	}

	/**
	 * Set the encoder.
	 *
	 * @param  \LEWP\Encoder    $encoder    The encoder instance to use for the request.
	 * @return void
	 */
	public function set_encoder( $encoder ) {
		$this->encoder = $encoder;
	}

	/**
	 * Get the response body.
	 *
	 * @return array The body content received in the response.
	 */
	public function get_response_body() {
		return $this->response_body;
	}

	/**
	 * Set the response body.
	 *
	 * @param array $body The body content received in the response.
	 */
	public function set_response_body( array $body ) {
		$this->response_body = $body;
	}

	/**
	 * Get the response headers.
	 *
	 * @return array The headers received in the response.
	 */
	public function get_response_headers() {
		return $this->response_headers;
	}

	/**
	 * Set the response headers.
	 *
	 * @param array $headers The headers received in the response.
	 */
	public function set_response_headers( array $headers ) {
		$this->response_headers = $headers;
	}

	/**
	 * Get a request header by its key.
	 *
	 * @param  string $name   The header name.
	 * @return string The header value.
	 */
	public function get_request_header( $name ) {
		if ( array_key_exists( $name, $this->request_headers ) ) {
			return $this->request_headers[ $name ];
		}
		return false;
	}

	/**
	 * Set a request header.
	 *
	 * @param string $name   The header name.
	 * @param string $header The header value.
	 */
	public function set_request_header( $name, $value ) {
		$this->request_headers[ $name ] = $value;
	}

	/**
	 * Get all of the request headers.
	 *
	 * @return array Array of request headers keyed by name.
	 */
	public function get_request_headers() {
		$headers = $this->request_headers;

		$nonce = $this->get_request_nonce();

		if ( $nonce ) {
			$headers['Replay-Nonce'] = $nonce;
		}

		return $headers;
	}

	/**
	 * Get the nonce for the request.
	 *
	 * @return string    The nonce to protect the request against CSRF.
	 */
	public function get_request_nonce() {
		return $this->request_nonce;
	}

	/**
	 * Set the nonce for the request.
	 *
	 * @param string    $request_nonce    The nonce to protect the request against CSRF.
	 */
	public function set_request_nonce( $request_nonce ) {
		$this->request_nonce = $request_nonce;
	}

	/**
	 * Get the NonceCollector object.
	 *
	 * @return \LEWP\NonceCollector    The NonceCollector object to manage nonces.
	 */
	public function get_nonce_collector() {
		return $this->nonce_collector;
	}

	/**
	 * Set the NonceCollector.
	 *
	 * @param \LEWP\NonceCollector    $request_nonce    The NonceCollector object.
	 */
	public function set_nonce_collector( $request_nonce ) {
		$this->nonce_collector = $request_nonce;
	}

	/**
	 * Get the nonce for the response.
	 *
	 * @return string    The nonce to protect the next request against CSRF.
	 */
	public function get_response_nonce() {
		return $this->response_nonce;
	}

	/**
	 * Set the nonce for the response.
	 *
	 * @param string    $response_nonce    The nonce to protect the next request against CSRF.
	 */
	public function set_response_nonce( $response_nonce ) {
		$this->response_nonce = $response_nonce;
	}

	/**
	 * Get the type of ACME request.
	 *
	 * @return string    The ACME request type (e.g., new-reg, new-cert).
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Set the type of ACME request.
	 *
	 * @param string    $type    The ACME request type (e.g., new-reg, new-cert).
	 */
	public function set_type( $type ) {
		$this->type = $type;
	}

	/**
	 * Get the request method.
	 *
	 * @return string    The method used for the request.
	 */
	public function get_method() {
		return $this->method;
	}

	/**
	 * Set the request method.
	 *
	 * @param string    $method    The method used for the request.
	 */
	public function set_method( $method ) {
		$this->method = $method;
	}

	/**
	 * Get the request signature.
	 *
	 * @return string The signature used for the request.
	 */
	public function get_signature() {
		return $this->signature;
	}

	/**
	 * Set the request signature.
	 *
	 * @param string $signature The signature used for the request.
	 */
	public function set_signature( $signature ) {
		$this->signature = $signature;
	}

}
