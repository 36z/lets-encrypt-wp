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

if ( defined( 'WP_CLI' ) && true === WP_CLI) {
	new WordPress\Commands\Cert\Cert();
}