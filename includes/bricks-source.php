<?php
defined('ABSPATH') || exit;

function playbrick_bricks_tailwind_source_path( $settings = null ) {
	if ( $settings === null ) $settings = get_option( 'playbrick_settings', [] );
	$playground_path = $settings['playground_path'] ?? ( ABSPATH . 'playground' );
	return untrailingslashit( $playground_path ) . '/.playbrick/bricks-sources.html';
}

function playbrick_bricks_tailwind_raw_source_path( $settings = null ) {
	if ( $settings === null ) $settings = get_option( 'playbrick_settings', [] );
	$playground_path = $settings['playground_path'] ?? ( ABSPATH . 'playground' );
	return untrailingslashit( $playground_path ) . '/.playbrick/bricks-sources.txt';
}

function playbrick_bricks_tailwind_custom_css_path( $settings = null ) {
	if ( $settings === null ) $settings = get_option( 'playbrick_settings', [] );
	$playground_path = $settings['playground_path'] ?? ( ABSPATH . 'playground' );
	return untrailingslashit( $playground_path ) . '/.playbrick/bricks-custom.css';
}

function playbrick_bricks_tailwind_theme_css_path( $settings = null ) {
	if ( $settings === null ) $settings = get_option( 'playbrick_settings', [] );
	$playground_path = $settings['playground_path'] ?? ( ABSPATH . 'playground' );
	return untrailingslashit( $playground_path ) . '/.playbrick/bricks-theme.css';
}

function playbrick_generate_bricks_tailwind_source( $settings = null ) {
	if ( $settings === null ) $settings = get_option( 'playbrick_settings', [] );
	$playground_path = $settings['playground_path'] ?? ( ABSPATH . 'playground' );
	$classes         = playbrick_collect_bricks_tailwind_classes();
	$custom_css      = playbrick_collect_bricks_tailwind_custom_css();
	$classes         = array_merge( $classes, playbrick_extract_tailwind_apply_classes( $custom_css ) );
	$theme_css       = playbrick_build_bricks_theme_css( playbrick_get_bricks_color_palette() );
	$path            = playbrick_write_bricks_tailwind_source( $playground_path, $classes, $custom_css, $theme_css );
	$raw_path        = playbrick_bricks_tailwind_raw_source_path( $settings );
	$custom_css_path = playbrick_bricks_tailwind_custom_css_path( $settings );
	$theme_css_path  = playbrick_bricks_tailwind_theme_css_path( $settings );

	return [
		'path'            => $path,
		'raw_path'        => $raw_path,
		'custom_css_path' => $custom_css_path,
		'theme_css_path'  => $theme_css_path,
		'classes'         => $classes,
		'count'           => count( $classes ),
	];
}

function playbrick_collect_bricks_tailwind_custom_css() {
	$chunks = [];

	foreach ( playbrick_get_bricks_global_classes() as $global_class ) {
		if ( ! is_array( $global_class ) ) continue;

		$name     = $global_class['name'] ?? '';
		$settings = $global_class['settings'] ?? [];

		if ( ! is_string( $name ) || $name === '' || ! is_array( $settings ) ) continue;

		$selector = '.' . ltrim( trim( $name ), '.' );
		$chunks   = array_merge( $chunks, playbrick_extract_tailwind_custom_css_from_settings( $settings, $selector ) );
	}

	return trim( implode( "\n\n", array_filter( $chunks ) ) );
}

function playbrick_extract_tailwind_custom_css_from_settings( array $settings, $selector ) {
	$chunks = [];

	foreach ( $settings as $key => $value ) {
		if ( strpos( (string) $key, '_cssCustom' ) !== 0 || ! is_string( $value ) || trim( $value ) === '' ) continue;

		$chunks[] = playbrick_normalize_tailwind_custom_css_block( $value, $selector, $key );
	}

	return $chunks;
}

function playbrick_normalize_tailwind_custom_css_block( $css, $selector, $source_key = '_cssCustom' ) {
	$css = trim( (string) $css );
	$css = str_replace( '%root%', $selector, $css );

	if ( strpos( $css, '{' ) !== false ) {
		return "/* {$source_key} */\n" . $css;
	}

	$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\R/', $css ) ) ) );
	$lines = array_map(
		function ( $line ) {
			if ( $line !== '' && $line[0] === '@' && substr( $line, -1 ) !== ';' ) {
				return $line . ';';
			}

			return $line;
		},
		$lines
	);

	return "/* {$source_key} */\n{$selector} {\n  " . implode( "\n  ", $lines ) . "\n}";
}

function playbrick_extract_tailwind_apply_classes( $css ) {
	$classes = [];

	if ( ! is_string( $css ) || trim( $css ) === '' ) {
		return [];
	}

	if ( preg_match_all( '/@apply\s+([^;{}]+)/', $css, $matches ) ) {
		foreach ( $matches[1] as $apply_value ) {
			$classes = array_merge( $classes, preg_split( '/\s+/', trim( $apply_value ) ) );
		}
	}

	return playbrick_normalize_class_list( $classes );
}

function playbrick_collect_bricks_tailwind_classes() {
	$classes        = [];
	$global_classes = playbrick_get_bricks_global_class_map();

	foreach ( $global_classes as $class_name ) {
		$classes[] = $class_name;
	}

	foreach ( playbrick_get_bricks_content_rows() as $row ) {
		$data    = playbrick_maybe_unserialize_bricks_value( $row );
		$classes = array_merge( $classes, playbrick_extract_classes_from_bricks_data( $data, $global_classes ) );
	}

	return playbrick_normalize_class_list( $classes );
}

function playbrick_get_bricks_global_class_map() {
	$global_classes = playbrick_get_bricks_global_classes();
	$map            = [];

	if ( ! is_array( $global_classes ) ) {
		return $map;
	}

	foreach ( $global_classes as $global_class ) {
		if ( ! is_array( $global_class ) ) continue;
		$id   = $global_class['id'] ?? '';
		$name = $global_class['name'] ?? '';

		if ( $id && $name ) {
			$map[ $id ] = $name;
		}
	}

	return $map;
}

function playbrick_get_bricks_global_classes() {
	$option_name    = defined( 'BRICKS_DB_GLOBAL_CLASSES' ) ? BRICKS_DB_GLOBAL_CLASSES : 'bricks_global_classes';
	$global_classes = get_option( $option_name, [] );

	return is_array( $global_classes ) ? $global_classes : [];
}

function playbrick_get_bricks_color_palette() {
	$option_name = defined( 'BRICKS_DB_COLOR_PALETTE' ) ? BRICKS_DB_COLOR_PALETTE : 'bricks_color_palette';
	$palette     = get_option( $option_name, [] );

	return is_array( $palette ) ? $palette : [];
}

function playbrick_bricks_color_css_var_name( array $color ) {
	// Bricks emits the variable referenced in 'raw' when present (e.g. the
	// default palette maps each color to var(--bricks-color-amber)); otherwise
	// the frontend variable is --bricks-color-{id}.
	$raw = isset( $color['raw'] ) && is_string( $color['raw'] ) ? trim( $color['raw'] ) : '';

	if ( $raw !== '' && preg_match( '/var\(\s*(--[A-Za-z0-9_-]+)\s*\)/', $raw, $matches ) ) {
		return $matches[1];
	}

	$id = isset( $color['id'] ) ? trim( (string) $color['id'] ) : '';

	if ( $id === '' || preg_match( '/[^A-Za-z0-9_-]/', $id ) ) {
		return '';
	}

	return '--bricks-color-' . $id;
}

function playbrick_bricks_theme_color_slug( array $color ) {
	$name = isset( $color['name'] ) && is_string( $color['name'] ) ? trim( $color['name'] ) : '';

	if ( $name !== '' ) {
		$slug = sanitize_title( remove_accents( $name ) );

		if ( $slug !== '' ) {
			return $slug;
		}
	}

	// Colors without a name (e.g. the Bricks default palette): derive the slug
	// from the raw CSS variable (--bricks-color-amber → amber).
	$raw = isset( $color['raw'] ) && is_string( $color['raw'] ) ? trim( $color['raw'] ) : '';

	if ( $raw !== '' && preg_match( '/var\(\s*--(?:bricks-color-)?([A-Za-z0-9_-]+)\s*\)/', $raw, $matches ) ) {
		$slug = sanitize_title( remove_accents( $matches[1] ) );

		if ( $slug !== '' ) {
			return $slug;
		}
	}

	$id = isset( $color['id'] ) ? trim( (string) $color['id'] ) : '';

	return sanitize_title( $id );
}

function playbrick_collect_bricks_theme_colors( array $palette ) {
	$colors = [];

	foreach ( $palette as $palette_group ) {
		if ( ! is_array( $palette_group ) || empty( $palette_group['colors'] ) || ! is_array( $palette_group['colors'] ) ) continue;

		foreach ( $palette_group['colors'] as $color ) {
			if ( ! is_array( $color ) ) continue;

			$css_var = playbrick_bricks_color_css_var_name( $color );
			$slug    = playbrick_bricks_theme_color_slug( $color );

			if ( $css_var === '' || $slug === '' ) continue;

			$base_slug = $slug;
			$suffix    = 2;

			while ( isset( $colors[ $slug ] ) && $colors[ $slug ] !== $css_var ) {
				$slug = $base_slug . '-' . $suffix;
				$suffix++;
			}

			$colors[ $slug ] = $css_var;
		}
	}

	return $colors;
}

function playbrick_build_bricks_theme_css( array $palette ) {
	$css = "/* Auto-generated by PlayBrick. Do not edit — regenerated from the Bricks color palette. */\n";

	$colors = playbrick_collect_bricks_theme_colors( $palette );

	if ( ! $colors ) {
		return $css . "/* No Bricks color palette found. Save a palette in Bricks (Settings → Color palette), then rebuild. */\n";
	}

	$css .= "@theme inline {\n";

	foreach ( $colors as $slug => $css_var ) {
		$css .= "  --color-{$slug}: var({$css_var});\n";
	}

	return $css . "}\n";
}


function playbrick_get_bricks_content_rows() {
	global $wpdb;

	if ( ! $wpdb || empty( $wpdb->postmeta ) || empty( $wpdb->posts ) ) {
		return [];
	}

	$pattern = '\\_bricks\\_%\\_2';

	return $wpdb->get_col(
		$wpdb->prepare(
			"SELECT pm.meta_value
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key LIKE %s
			 AND p.post_status NOT IN ('trash', 'auto-draft', 'inherit')",
			$pattern
		)
	);
}

function playbrick_maybe_unserialize_bricks_value( $value ) {
	if ( function_exists( 'maybe_unserialize' ) ) {
		return maybe_unserialize( $value );
	}

	if ( is_string( $value ) ) {
		$unserialized = @unserialize( $value );
		if ( $unserialized !== false || $value === 'b:0;' ) {
			return $unserialized;
		}
	}

	return $value;
}

function playbrick_extract_classes_from_bricks_data( $data, array $global_class_map = [] ) {
	$classes = [];

	if ( is_array( $data ) ) {
		foreach ( $data as $key => $value ) {
			if ( $key === '_cssClasses' && is_string( $value ) ) {
				$classes = array_merge( $classes, preg_split( '/\s+/', $value ) );
				continue;
			}

			if ( $key === '_cssGlobalClasses' && is_array( $value ) ) {
				foreach ( $value as $class_id ) {
					if ( isset( $global_class_map[ $class_id ] ) ) {
						$classes[] = $global_class_map[ $class_id ];
					}
				}
				continue;
			}

			if ( $key === '_attributes' && is_array( $value ) ) {
				$classes = array_merge( $classes, playbrick_extract_classes_from_bricks_attributes( $value ) );
			}

			$classes = array_merge( $classes, playbrick_extract_classes_from_bricks_data( $value, $global_class_map ) );
		}
	}

	return playbrick_normalize_class_list( $classes );
}

function playbrick_extract_classes_from_bricks_attributes( array $attributes ) {
	$classes = [];

	foreach ( $attributes as $attribute ) {
		if ( ! is_array( $attribute ) ) continue;
		$name  = $attribute['name'] ?? '';
		$value = $attribute['value'] ?? '';

		if ( is_string( $name ) && in_array( strtolower( $name ), [ 'class', 'classes' ], true ) && is_string( $value ) ) {
			$classes = array_merge( $classes, preg_split( '/\s+/', $value ) );
		}
	}

	return $classes;
}

function playbrick_write_bricks_tailwind_source( $playground_path, array $classes, $custom_css = '', $theme_css = '' ) {
	$classes = playbrick_normalize_class_list( $classes );
	$dir     = untrailingslashit( $playground_path ) . '/.playbrick';
	$path    = $dir . '/bricks-sources.html';
	$raw_path = $dir . '/bricks-sources.txt';
	$custom_css_path = $dir . '/bricks-custom.css';
	$theme_css_path  = $dir . '/bricks-theme.css';

	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}

	$html  = "<!-- Generated by PlayBrick. Do not edit manually. -->\n";
	$html .= '<div class="' . htmlspecialchars( implode( ' ', $classes ), ENT_QUOTES, 'UTF-8' ) . '"></div>' . "\n";
	$raw   = "# Generated by PlayBrick. Do not edit manually.\n" . implode( "\n", $classes ) . "\n";
	$custom_css = trim( (string) $custom_css );
	$custom_css = "/* Generated by PlayBrick. Do not edit manually. */\n" . ( $custom_css ? $custom_css . "\n" : '' );
	$theme_css  = trim( (string) $theme_css );

	if ( file_exists( $path ) && file_get_contents( $path ) === $html ) {
		if (
			file_exists( $raw_path ) && file_get_contents( $raw_path ) === $raw &&
			file_exists( $custom_css_path ) && file_get_contents( $custom_css_path ) === $custom_css &&
			( $theme_css === '' || ( file_exists( $theme_css_path ) && file_get_contents( $theme_css_path ) === $theme_css . "\n" ) )
		) {
			return $path;
		}
	}

	file_put_contents( $path, $html, LOCK_EX );
	file_put_contents( $raw_path, $raw, LOCK_EX );
	file_put_contents( $custom_css_path, $custom_css, LOCK_EX );

	if ( $theme_css !== '' ) {
		file_put_contents( $theme_css_path, $theme_css . "\n", LOCK_EX );
	}

	return $path;
}

function playbrick_normalize_class_list( array $classes ) {
	$normalized = [];

	foreach ( $classes as $class ) {
		$class = trim( (string) $class );
		if ( $class === '' ) continue;
		if ( preg_match( '/\s|[\x00-\x1F\x7F]/', $class ) ) continue;
		$normalized[] = $class;
	}

	$normalized = array_values( array_unique( $normalized ) );
	sort( $normalized, SORT_NATURAL );

	return $normalized;
}
