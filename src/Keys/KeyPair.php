<?php

namespace LEWP\Keys;

class KeyPair {
	private $id = '';

	private $public_key = '';

	private $private_key = '';

	/**
	 * @var resource
	 */
	private $resource = '';

	private $csr = '';

	/**
	 * Construct the object.
	 *
	 * @param  string     $id    The key pair ID.
	 */
	public function __construct( $id, $private_key = '' ) {
		$this->set_id( $id );
		$this->private_key = $private_key;
	}

	/**
	 * Set the key pair ID.
	 *
	 * @param string $id The key pair ID.
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Get the key pair ID.
	 *
	 * @return string The key pair ID.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Generates a new public and private key pair.
	 *
	 * @param  string $passphrase The passphrase to encrypt the private key.
	 */
	public function generate( $passphrase ) {
		if ( empty( $this->private_key ) ) {
			$config = array(
				'digest_alg'       => 'sha256',
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			);

			$this->resource = openssl_pkey_new( $config );
		} else {
			$this->resource = openssl_pkey_get_private($this->private_key, $passphrase);
		}

		openssl_pkey_export( $this->resource, $private_key, $passphrase );

		$details = openssl_pkey_get_details( $this->resource );

		$this->private_key = $private_key;
		$this->public_key  = $details['key'];
		$this->details     = $details;

	}

	/**
	 * Reads a keypair from elsewhere (eg. persistent storage) and places it into the KeyPair object.
	 *
	 * @param array $keypair {
	 *     An array representing the key pair.
	 *
	 *     @type string $public  The public key.
	 *     @type string $private The private key.
	 * }
	 */
	public function read( array $keypair ) {

		$this->private_key = $keypair['private'];
		$this->public_key  = $keypair['public'];

	}

	/**
	 * Get the public key.
	 *
	 * @return string The public key.
	 */
	public function get_public_key() {
		return $this->public_key;
	}

	/**
	 * Get the encrypted private key.
	 *
	 * @return string The encrypted private key in PEM format.
	 */
	public function get_private_key() {
		return $this->private_key;
	}

	public function get_csr() {
		return $this->csr;
	}

	/**
	 * Export the decrypted private key resource.
	 *
	 * @param  string $passphrase The passphrase to decrypt the private key.
	 * @return resource|bool The decrypted private key resource, or false on failure.
	 */
	public function export_private_key( $passphrase ) {
		return openssl_pkey_get_private( $this->private_key, $passphrase );
	}

	public function thumbprint() {
		$encoder = new \LEWP\Base64UrlSafeEncoder;
		$encoded_e = $encoder->encode($this->details['rsa']['e']);
		$encoded_n = $encoder->encode($this->details['rsa']['n']);

		$key_string = '{"e":"' . $encoded_e . '","kty":"RSA","n":"' . $encoded_n . '"}';
		$hash = hash( 'sha256', $key_string, true );

		return $encoder->encode( $hash );
	}

	public function generate_csr( $dn, $configargs = array(), $extraattribs = array() ) {
		$this->csr = openssl_csr_new( $dn, $this->resource, $configargs );
		return $this->csr;
	}

	public function get_csr_pem( $csr_resource ) {
		openssl_csr_export($csr_resource, $csrout);

		var_dump( $csrout );

		$lines = explode( "\n", trim( $csrout ) );

		// Remove last and first line:
		unset( $lines[ count( $lines ) - 1 ] );
		unset( $lines[0] );

		// Join remaining lines:
		$result = implode( '', $lines );

		var_dump($result);

		return $result;
	}

	public function generate_csr_2( $dn ) {
		set_time_limit(300);
		$privKey = new \phpseclib\Crypt\RSA();
		extract($privKey->createKey(2048));
		$privKey->loadKey($privatekey); // $privatekey comes from the extract statement above @todo change this

		$x509 = new \phpseclib\File\X509();
		$x509->setPrivateKey($privKey);

//		foreach ( $dn as $prop => $value ) {
//			$x509->setDNProp( $prop, $value );
//		}

		$x509->setDomain('www.tollmanz.com');

//		$x509->setExtension('subjectAltName', 'DNS:tollmanz.com, DNS:www.tollmanz.com');
		$x509->setExtension('id-ce-keyUsage', array('digitalSignature', 'keyEncipherment'));
		$csr = $x509->signCSR( 'sha256WithRSAEncryption' );

		$asci = $x509->saveCSR($csr, \phpseclib\File\X509::FORMAT_PEM);

		var_dump($asci);

		$lines = explode( "\r\n", trim( $asci ) );

		// Remove last and first line:
		unset( $lines[ count( $lines ) - 1 ] );
		unset( $lines[0] );

		// Join remaining lines:
		$result = implode( '', $lines );

		var_dump($result);
		return $result;
	}
}