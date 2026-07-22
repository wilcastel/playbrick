<?php
$wp_load = $argv[1] ?? dirname( __DIR__ ) . '/wp-load.php';

if ( ! is_file( $wp_load ) ) {
	fwrite( STDERR, "[PlayBrick] WordPress bootstrap not found. Skipping Bricks source export.\n" );
	exit( 0 );
}

require_once $wp_load;

if ( ! function_exists( 'playbrick_generate_bricks_tailwind_source' ) ) {
	fwrite( STDERR, "[PlayBrick] Plugin helper not available. Skipping Bricks source export.\n" );
	exit( 0 );
}

$result = playbrick_generate_bricks_tailwind_source();
$count  = $result['count'] ?? 0;
$path   = $result['path'] ?? '';
$raw_path = $result['raw_path'] ?? '';
$custom_css_path = $result['custom_css_path'] ?? '';
$theme_css_path  = $result['theme_css_path'] ?? '';

if ( ! $path ) {
	fwrite( STDERR, "[PlayBrick] Could not write Bricks source files. Check that the playground .playbrick directory is writable.\n" );
	exit( 1 );
}

fwrite( STDOUT, "[PlayBrick] Exported {$count} Bricks classes to {$path}" . ( $raw_path ? ", {$raw_path}" : '' ) . ( $custom_css_path ? ", {$custom_css_path}" : '' ) . ( $theme_css_path ? ", {$theme_css_path}" : '' ) . "\n" );
