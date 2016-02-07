<?php

namespace LEWP;

/**
 * Class Encoder
 *
 * A class for handling encoding and decoding with the special requirements specified by [RFC7515](http://tools.ietf.org/html/rfc7515)
 * and used by the ACME protocol.
 *
 * This class is a copy of the class from the [Namshi/Jose package](https://github.com/namshi/jose/blob/341dd2fa9f3a93f66e93bcd1f6fb81638c2015e3/src/Namshi/JOSE/Base64/Base64Encoder.php)
 * with styling changes.
 *
 * @package LEWP
 */
class Encoder {
	/**
	 * Encode data.
	 *
	 * Encode data using base64, with extra character replacement as needed. These special requirements are described
	 * in [RFC7515](http://tools.ietf.org/html/rfc7515) and specified by the ACME spec.
	 *
	 * @param  mixed    $data    The data to encode.
	 * @return string            base64 encoded string.
	 */
	public function encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Decode a string.
	 *
	 * This uses base64 to decode a string and reverse the substitutions completed in the encoding.
	 *
	 * @param  string    $data    The string to decode.
	 * @return mixed              The decoded data.
	 */
	public function decode( $data ) {
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}
}
