<?php
defined('ABSPATH') || exit;

/**
 * Default Bricks Style Manager token seed.
 *
 * Pure builders and a pure planner — no WP I/O. The `admin_post` handler in
 * includes/admin.php reads Bricks' options, asks playbrick_bricks_token_seed_plan()
 * what to write, and performs the update_option() calls itself.
 */

/**
 * One color palette group with 6 starting colors.
 *
 * No 'raw' key: the read path (playbrick_bricks_color_css_var_name() in
 * includes/bricks-source.php) falls back to --bricks-color-{id} when 'raw'
 * is absent.
 *
 * @return array
 */
function playbrick_default_bricks_color_palette() {
	return [
		[
			'id'     => 'playbrick',
			'name'   => 'PlayBrick',
			'colors' => [
				[ 'id' => 'pbprimary',   'name' => 'Primary',   'hex' => '#0F766E' ],
				[ 'id' => 'pbsecondary', 'name' => 'Secondary', 'hex' => '#0EA5E9' ],
				[ 'id' => 'pbaccent',    'name' => 'Accent',    'hex' => '#F59E0B' ],
				[ 'id' => 'pbneutral',   'name' => 'Neutral',   'hex' => '#64748B' ],
				[ 'id' => 'pbsuccess',   'name' => 'Success',   'hex' => '#16A34A' ],
				[ 'id' => 'pbdanger',    'name' => 'Danger',    'hex' => '#DC2626' ],
			],
		],
	];
}

/**
 * 20 starting global variables (D1: no color-* entries here — colors live
 * only in the palette above).
 *
 * @return array
 */
function playbrick_default_bricks_global_variables() {
	return [
		[ 'id' => 'pb-space-3xs', 'name' => 'space-3xs', 'value' => '0.25rem',  'category' => 'spacing' ],
		[ 'id' => 'pb-space-2xs', 'name' => 'space-2xs', 'value' => '0.5rem',   'category' => 'spacing' ],
		[ 'id' => 'pb-space-xs',  'name' => 'space-xs',  'value' => '0.75rem',  'category' => 'spacing' ],
		[ 'id' => 'pb-space-sm',  'name' => 'space-sm',  'value' => '1rem',     'category' => 'spacing' ],
		[ 'id' => 'pb-space-md',  'name' => 'space-md',  'value' => '1.5rem',   'category' => 'spacing' ],
		[ 'id' => 'pb-space-lg',  'name' => 'space-lg',  'value' => '2rem',     'category' => 'spacing' ],
		[ 'id' => 'pb-space-xl',  'name' => 'space-xl',  'value' => '3rem',     'category' => 'spacing' ],
		[ 'id' => 'pb-space-2xl', 'name' => 'space-2xl', 'value' => '4rem',     'category' => 'spacing' ],
		[ 'id' => 'pb-text-xs',   'name' => 'text-xs',   'value' => '0.75rem',  'category' => 'typography' ],
		[ 'id' => 'pb-text-sm',   'name' => 'text-sm',   'value' => '0.875rem', 'category' => 'typography' ],
		[ 'id' => 'pb-text-base', 'name' => 'text-base', 'value' => '1rem',     'category' => 'typography' ],
		[ 'id' => 'pb-text-lg',   'name' => 'text-lg',   'value' => '1.125rem', 'category' => 'typography' ],
		[ 'id' => 'pb-text-xl',   'name' => 'text-xl',   'value' => '1.5rem',   'category' => 'typography' ],
		[ 'id' => 'pb-text-2xl',  'name' => 'text-2xl',  'value' => '2rem',     'category' => 'typography' ],
		[ 'id' => 'pb-leading-tight',  'name' => 'leading-tight',  'value' => '1.2',                          'category' => 'typography' ],
		[ 'id' => 'pb-leading-normal', 'name' => 'leading-normal', 'value' => '1.5',                          'category' => 'typography' ],
		[ 'id' => 'pb-font-sans', 'name' => 'font-sans', 'value' => 'Inter, system-ui, sans-serif',           'category' => 'typography' ],
		[ 'id' => 'pb-radius-sm', 'name' => 'radius-sm', 'value' => '0.25rem',                                'category' => 'radii' ],
		[ 'id' => 'pb-radius-md', 'name' => 'radius-md', 'value' => '0.5rem',                                 'category' => 'radii' ],
		[ 'id' => 'pb-shadow-sm', 'name' => 'shadow-sm', 'value' => '0 1px 2px rgb(0 0 0 / 0.05)',            'category' => 'shadows' ],
	];
}

/**
 * Bricks' color-palette option name.
 *
 * MUST stay identical to the ternary in includes/bricks-source.php:164
 * (playbrick_get_bricks_color_palette()) — that file is frozen (A5), so this
 * duplicate is the deliberate drift-guard boundary; a test independently
 * re-derives the same literal to catch divergence.
 *
 * @return string
 */
function playbrick_bricks_color_palette_option_name() {
	return defined( 'BRICKS_DB_COLOR_PALETTE' ) ? BRICKS_DB_COLOR_PALETTE : 'bricks_color_palette';
}

/**
 * Decide, per option, whether to seed or skip (D2: evaluated independently).
 *
 * Pure — no WP I/O. The handler passes in the *normalized* reader output
 * (playbrick_get_bricks_color_palette() / playbrick_get_bricks_global_variables()
 * already coerce non-arrays to []), so a corrupt/scalar option counts as empty.
 *
 * @param array $current_palette
 * @param array $current_variables
 * @return array{palette: array, variables: array}
 */
function playbrick_bricks_token_seed_plan( array $current_palette, array $current_variables ) {
	$plan = [];

	$plan['palette'] = empty( $current_palette )
		? [ 'action' => 'seed', 'value' => playbrick_default_bricks_color_palette() ]
		: [ 'action' => 'skip' ];

	$plan['variables'] = empty( $current_variables )
		? [ 'action' => 'seed', 'value' => playbrick_default_bricks_global_variables() ]
		: [ 'action' => 'skip' ];

	return $plan;
}
