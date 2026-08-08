<?php
namespace PlayBrick\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class SeedBricksTokensTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	// --- T1: palette builder shape ------------------------------------------------

	public function test_default_palette_has_one_group_and_six_colors(): void {
		$palette = playbrick_default_bricks_color_palette();

		$this->assertCount( 1, $palette );
		$this->assertArrayHasKey( 'id', $palette[0] );
		$this->assertArrayHasKey( 'name', $palette[0] );
		$this->assertArrayHasKey( 'colors', $palette[0] );
		$this->assertCount( 6, $palette[0]['colors'] );

		foreach ( $palette[0]['colors'] as $color ) {
			$this->assertArrayHasKey( 'id', $color );
			$this->assertArrayHasKey( 'name', $color );
			$this->assertArrayHasKey( 'hex', $color );
			$this->assertMatchesRegularExpression( '/^#[0-9A-Fa-f]{6}$/', $color['hex'] );
		}
	}

	// --- T2: global variable builder shape -----------------------------------------

	public function test_default_global_variables_has_twenty_entries_with_valid_shape(): void {
		$variables = playbrick_default_bricks_global_variables();

		$this->assertCount( 20, $variables );

		foreach ( $variables as $variable ) {
			$this->assertMatchesRegularExpression( '/^pb-[A-Za-z0-9_-]+$/', $variable['id'] );
			$this->assertDoesNotMatchRegularExpression( '/[;{}]/', $variable['value'] );
		}
	}

	// --- T3: D3/DC-4 invariant — every variable resolves to a non-empty theme token

	public function test_every_default_global_variable_resolves_to_a_theme_token(): void {
		foreach ( playbrick_default_bricks_global_variables() as $variable ) {
			$this->assertNotSame(
				'',
				playbrick_bricks_global_variable_theme_token( $variable ),
				'Variable "' . $variable['name'] . '" must resolve to a non-empty theme token.'
			);
		}
	}

	// --- T4: full seed set -> 26 collision-free tokens (D1 + SC5) -------------------

	public function test_full_seed_set_yields_twenty_six_collision_free_tokens(): void {
		$css = playbrick_build_bricks_theme_css(
			playbrick_default_bricks_color_palette(),
			playbrick_default_bricks_global_variables()
		);

		$this->assertSame( 26, substr_count( $css, "\n  --" ) );
		$this->assertDoesNotMatchRegularExpression( '/--[a-z0-9-]+-2:/', $css );
	}

	// --- A5 drift guard: option-name helper must match bricks-source.php:164 --------

	public function test_color_palette_option_name_matches_read_path_default(): void {
		$this->assertSame( 'bricks_color_palette', playbrick_bricks_color_palette_option_name() );
		$this->assertSame( playbrick_bricks_color_palette_option_name(), $this->playbrick_get_bricks_color_palette_option_name_reference() );
	}

	private function playbrick_get_bricks_color_palette_option_name_reference(): string {
		// Mirrors the literal ternary at includes/bricks-source.php:164 — kept
		// duplicated here (not extracted) so this test independently detects drift.
		return defined( 'BRICKS_DB_COLOR_PALETTE' ) ? \BRICKS_DB_COLOR_PALETTE : 'bricks_color_palette';
	}

	// --- T5/T6/T7: seed plan — pure, no mocks ---------------------------------------

	public function test_seed_plan_seeds_both_when_both_empty(): void {
		$plan = playbrick_bricks_token_seed_plan( [], [] );

		$this->assertSame( 'seed', $plan['palette']['action'] );
		$this->assertSame( playbrick_default_bricks_color_palette(), $plan['palette']['value'] );
		$this->assertSame( 'seed', $plan['variables']['action'] );
		$this->assertSame( playbrick_default_bricks_global_variables(), $plan['variables']['value'] );
	}

	public function test_seed_plan_skips_palette_and_seeds_variables_when_palette_populated(): void {
		$existing_palette = [ [ 'id' => 'existing', 'name' => 'Existing', 'colors' => [] ] ];

		$plan = playbrick_bricks_token_seed_plan( $existing_palette, [] );

		$this->assertSame( 'skip', $plan['palette']['action'] );
		$this->assertArrayNotHasKey( 'value', $plan['palette'] );
		$this->assertSame( 'seed', $plan['variables']['action'] );
	}

	public function test_seed_plan_skips_variables_and_seeds_palette_when_variables_populated(): void {
		$existing_variables = [ [ 'id' => 'existing', 'name' => 'space-existing', 'value' => '1rem' ] ];

		$plan = playbrick_bricks_token_seed_plan( [], $existing_variables );

		$this->assertSame( 'seed', $plan['palette']['action'] );
		$this->assertSame( 'skip', $plan['variables']['action'] );
		$this->assertArrayNotHasKey( 'value', $plan['variables'] );
	}

	public function test_seed_plan_skips_both_when_both_populated(): void {
		$existing_palette   = [ [ 'id' => 'existing', 'name' => 'Existing', 'colors' => [] ] ];
		$existing_variables = [ [ 'id' => 'existing', 'name' => 'space-existing', 'value' => '1rem' ] ];

		$plan = playbrick_bricks_token_seed_plan( $existing_palette, $existing_variables );

		$this->assertSame( 'skip', $plan['palette']['action'] );
		$this->assertSame( 'skip', $plan['variables']['action'] );
	}
}
