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
		$GLOBALS['wp_filter'][ $tag ][ $callback ] = $priority;
	}
}

if ( ! function_exists( 'has_action' ) ) {
	function has_action( $tag, $callback = false ) {
		if ( $callback === false ) {
			return ! empty( $GLOBALS['wp_filter'][ $tag ] );
		}

		return $GLOBALS['wp_filter'][ $tag ][ $callback ] ?? false;
	}
}

if ( ! function_exists( 'add_options_page' ) ) {
	function add_options_page( ...$args ) {
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {
		$GLOBALS['playbrick_activation_hook'] = $callback;
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

if ( ! function_exists( 'remove_accents' ) ) {
	function remove_accents( $text ) {
		$text = strtr(
			$text,
			[
				'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a',
				'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
				'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
				'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ø' => 'o',
				'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
				'ñ' => 'n', 'ç' => 'c', 'ß' => 's',
				'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'Ã' => 'A', 'Å' => 'A',
				'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
				'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
				'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Õ' => 'O', 'Ø' => 'O',
				'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
				'Ñ' => 'N', 'Ç' => 'C',
			]
		);

		if ( function_exists( 'iconv' ) ) {
			$transliterated = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $text );

			if ( $transliterated !== false ) {
				return $transliterated;
			}
		}

		return $text;
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		$title = remove_accents( $title );
		$title = strtolower( $title );
		$title = preg_replace( '/[^a-z0-9]+/', '-', $title );

		return trim( $title, '-' );
	}
}

require_once PLAYBRICK_DIR . 'includes/enqueue-strategy.php';
require_once PLAYBRICK_DIR . 'includes/scaffold.php';
require_once PLAYBRICK_DIR . 'includes/bricks-source.php';
require_once PLAYBRICK_DIR . 'includes/bricks-tokens-seed.php';
require_once PLAYBRICK_DIR . 'includes/enqueue.php';
require_once PLAYBRICK_DIR . 'includes/builder-panel.php';
require_once PLAYBRICK_DIR . 'includes/admin.php';
