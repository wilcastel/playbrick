<?php
/**
 * Test bootstrap — loads the plugin files with a minimal WordPress stub layer.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/playbrick-wp/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'PLAYBRICK_VERSION' ) ) {
	define( 'PLAYBRICK_VERSION', '1.0.0-test' );
}

if ( ! defined( 'PLAYBRICK_DIR' ) ) {
	define( 'PLAYBRICK_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'PLAYBRICK_URL' ) ) {
	define( 'PLAYBRICK_URL', 'https://example.com/wp-content/plugins/playbrick/' );
}

// Minimal WordPress helpers required to load plugin files without a full WP install.
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	}
}

if ( ! function_exists( 'add_options_page' ) ) {
	function add_options_page( ...$args ) {
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $string ) {
		return rtrim( $string, '/\\' );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {
		return str_replace( '\\', '/', $path );
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $path ) {
		return mkdir( $path, 0777, true ) || is_dir( $path );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return (string) $str;
	}
}

require_once PLAYBRICK_DIR . 'includes/enqueue-strategy.php';
require_once PLAYBRICK_DIR . 'includes/scaffold.php';
require_once PLAYBRICK_DIR . 'includes/enqueue.php';
require_once PLAYBRICK_DIR . 'includes/admin.php';
