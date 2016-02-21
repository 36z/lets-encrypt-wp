<?php

namespace LEWP\Commands;
use WP_CLI;

class Utils {
	public static function display_error( $message ) {
		WP_CLI::error( $message );
	}

	public static function display_success( $message ) {
		WP_CLI::success( $message );
	}

	public static function display_line( $message ) {
		WP_CLI::line( $message );
	}

	public static function display_array( $array, $offset = 0 ) {
		$spaces = $offset * 2;
		$spaces_as_string = '';

		for ( $i = 0; $i < $spaces; $i++ ) {
			$spaces_as_string .= ' ';
		}

		$prefix = ( $spaces > 0 ) ? $spaces_as_string . '- ' : '';

		foreach ( $array as $key => $value ) {
			self::display_line( $prefix . $key . ': ' . $value );
		}
	}

	public static function display_debug_message( $level, $v = '', $vv = '', $vvv = '' ) {
		if ( ! empty( $v ) && in_array( $level, array( 'v', 'vv', 'vvv' ) ) ) {
			( is_array( $v ) ) ? self::display_array( $v ) : self::display_line( $v );
		}

		if ( ! empty( $vv ) && in_array( $level, array( 'vv', 'vvv' ) ) ) {
			( is_array( $vv ) ) ? self::display_array( $vv, 1 ) : self::display_line( '  - ' .$vv );
		}

		if ( ! empty( $vvv ) && in_array( $level, array( 'vvv' ) ) ) {
			( is_array( $vvv ) ) ? self::display_array( $vvv, 2 ) : self::display_line( '    - ' . $vvv );
		}
	}

	public static function display_confirm( $message ) {
		WP_CLI::confirm( $message );
	}
}