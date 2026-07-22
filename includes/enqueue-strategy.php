<?php
defined('ABSPATH') || exit;

/**
 * Enqueue-strategy helpers.
 *
 * Centralizes the "who enqueues PlayBrick assets" decision and the
 * PHP snippet that is injected into a child theme when the child_theme
 * strategy is active.
 */

/**
 * Get the active enqueue strategy.
 *
 * @param array|null $settings Optional. PlayBrick settings array.
 * @return string 'plugin' or 'child_theme'.
 */
function playbrick_get_enqueue_strategy( $settings = null ) {
	if ( $settings === null ) {
		$settings = get_option( 'playbrick_settings', [] );
	}
	$strategy = $settings['enqueue_strategy'] ?? 'plugin';
	return in_array( $strategy, [ 'plugin', 'child_theme' ], true ) ? $strategy : 'plugin';
}

/**
 * Is the child-theme strategy currently active?
 *
 * @param array|null $settings Optional. PlayBrick settings array.
 * @return bool
 */
function playbrick_is_child_theme_strategy( $settings = null ) {
	return playbrick_get_enqueue_strategy( $settings ) === 'child_theme';
}

/**
 * Convert an absolute filesystem path to a site URL.
 *
 * Used by both the plugin enqueue and the child-theme snippet so both
 * paths stay consistent.
 *
 * @param string $path Absolute path.
 * @return string URL.
 */
function playbrick_path_to_url( $path ) {
	$path = untrailingslashit( $path );
	$path_norm = wp_normalize_path( $path );
	$upload = wp_upload_dir();
	$roots = [
		[ untrailingslashit( $upload['basedir'] ), untrailingslashit( $upload['baseurl'] ) ],
		[ untrailingslashit( WP_CONTENT_DIR ), untrailingslashit( content_url() ) ],
		[ untrailingslashit( ABSPATH ), untrailingslashit( get_home_url() ) ],
	];

	foreach ( $roots as $root ) {
		$base_path = wp_normalize_path( $root[0] );
		if ( $path_norm === $base_path || strpos( $path_norm, $base_path . '/' ) === 0 ) {
			$relative = ltrim( substr( $path_norm, strlen( $base_path ) ), '/' );
			return $relative === '' ? $root[1] : $root[1] . '/' . $relative;
		}
	}

	return str_replace( untrailingslashit( ABSPATH ), untrailingslashit( get_home_url() ), $path );
}

/**
 * Build the PHP snippet that a child theme loads to enqueue PlayBrick assets.
 *
 * The snippet is self-defending: it reads the current strategy at runtime and
 * returns early unless the strategy is child_theme. This prevents double
 * enqueue if the user switches back to the plugin strategy.
 *
 * @return string PHP code (without an opening <?php tag).
 */
function playbrick_render_child_theme_snippet() {
	return <<<'PHP'
add_action( 'wp_enqueue_scripts', function () {
	$settings = get_option( 'playbrick_settings', [] );
	if ( ( $settings['enqueue_strategy'] ?? 'plugin' ) !== 'child_theme' ) {
		return;
	}

	$env             = $settings['env']             ?? 'dev';
	$mode            = $settings['scaffold_mode']     ?? 'classic';
	$default_playground_path = rtrim( WP_CONTENT_DIR, '/\\' ) . '/playground';
	$playground_path = $settings['playground_path']   ?? $default_playground_path;
	$out_dir         = $settings['out_dir']           ?? ( wp_upload_dir()['basedir'] . '/assets' );

	if ( ! function_exists( 'playbrick_path_to_url' ) ) {
		return;
	}

	if ( $env === 'dev' ) {
		$playground_url = playbrick_path_to_url( $playground_path );
		$js_path        = $playground_path . '/dev.js';
		$js_url         = $playground_url . '/dev.js';

		if ( $mode === 'tailwind' ) {
			$css_path = $playground_path . '/dev.built.css';
			$css_url = $playground_url . '/dev.built.css';
		} else {
			$css_path = $playground_path . '/dev.css';
			$css_url = $playground_url . '/dev.css';
		}
	} else {
		$out_dir = untrailingslashit( $out_dir );
		$base     = playbrick_path_to_url( $out_dir );
		$css_path = $out_dir . '/style.min.css';
		$js_path  = $out_dir . '/script.min.js';
		$css_url = $base . '/style.min.css';
		$js_url  = $base . '/script.min.js';
	}

	$fallback_version = defined( 'PLAYBRICK_VERSION' ) ? PLAYBRICK_VERSION : null;

	wp_enqueue_style(
		'playbrick-styles',
		$css_url,
		[],
		file_exists( $css_path ) ? filemtime( $css_path ) : $fallback_version
	);

	wp_enqueue_script(
		'playbrick-scripts',
		$js_url,
		[],
		file_exists( $js_path ) ? filemtime( $js_path ) : $fallback_version,
		true
	);
}, 9999 );
PHP;
}

/**
 * Append a PHP snippet to an existing theme file, avoiding duplicates.
 *
 * @param string $file   Absolute path to the file.
 * @param string $snippet PHP code to append.
 */
function playbrick_append_php_snippet( $file, $snippet ) {
	$marker  = '// PlayBrick child-theme enqueue snippet — do not edit manually.';
	$content = file_get_contents( $file );

	if ( strpos( $content, $marker ) !== false ) {
		return;
	}

	$snippet = "\n" . $marker . "\n" . $snippet;
	$trimmed = rtrim( $content );

	if ( substr( $trimmed, -2 ) === '?>' ) {
		$content = substr( $trimmed, 0, -2 ) . rtrim( $snippet ) . "\n?>";
	} else {
		$content = $trimmed . $snippet;
	}

	file_put_contents( $file, $content, LOCK_EX );
}
