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

	// --- Handler ---------------------------------------------------------------

	private function mockBricksOptions( array $options ): void {
		Monkey\Functions\when( 'get_option' )->alias( function ( $name, $default = [] ) use ( $options ) {
			return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
		} );
	}

	private function mockHandlerAuth(): void {
		Monkey\Functions\when( 'check_admin_referer' )->justReturn( 1 );
		Monkey\Functions\when( 'current_user_can' )->justReturn( true );
		Monkey\Functions\when( 'admin_url' )->returnArg( 1 );
	}

	private function captureRedirect( ?string &$captured ): void {
		Monkey\Functions\expect( 'wp_redirect' )->once()->andReturnUsing( function ( $url ) use ( &$captured ) {
			$captured = $url;
			throw new \RuntimeException( 'redirect' );
		} );
	}

	// T14: registration (SC1)
	public function test_seed_action_is_registered(): void {
		$this->assertNotFalse( has_action( 'admin_post_playbrick_seed_bricks_tokens', 'playbrick_seed_bricks_tokens' ) );
	}

	// T12: non-admin user rejected (SC4)
	public function test_handler_rejects_non_admin_user(): void {
		Monkey\Functions\when( 'check_admin_referer' )->justReturn( 1 );
		Monkey\Functions\when( 'current_user_can' )->justReturn( false );
		Monkey\Functions\expect( 'wp_die' )->once()->andReturnUsing( function () {
			throw new \RuntimeException( 'died' );
		} );
		Monkey\Functions\expect( 'update_option' )->never();

		try {
			playbrick_seed_bricks_tokens( true );
			$this->fail( 'Expected wp_die to short-circuit the handler.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'died', $e->getMessage() );
		}
	}

	// T11: Bricks-inactive precondition (A2 injected flag)
	public function test_handler_redirects_with_error_when_bricks_inactive(): void {
		$this->mockHandlerAuth();
		Monkey\Functions\expect( 'update_option' )->never();

		$captured = null;
		$this->captureRedirect( $captured );

		try {
			playbrick_seed_bricks_tokens( false );
		} catch ( \RuntimeException $e ) {
			// Expected — wp_redirect mocked to throw.
		}

		$this->assertSame( 'options-general.php?page=playbrick&error=bricks_missing', $captured );
	}

	// T8: both options empty -> both seeded, redirect ...=all (SC2)
	public function test_handler_seeds_both_options_when_both_empty(): void {
		$this->mockHandlerAuth();
		$this->mockBricksOptions( [
			'bricks_color_palette'    => [],
			'bricks_global_variables' => [],
		] );

		$calls = [];
		Monkey\Functions\expect( 'update_option' )->twice()->andReturnUsing( function ( $name, $value ) use ( &$calls ) {
			$calls[] = [ $name, $value ];
			return true;
		} );

		$captured = null;
		$this->captureRedirect( $captured );

		try {
			playbrick_seed_bricks_tokens( true );
		} catch ( \RuntimeException $e ) {
			// Expected.
		}

		$this->assertSame( [ 'bricks_color_palette', playbrick_default_bricks_color_palette() ], $calls[0] );
		$this->assertSame( [ 'bricks_global_variables', playbrick_default_bricks_global_variables() ], $calls[1] );
		$this->assertSame( 'options-general.php?page=playbrick&bricks_tokens_seeded=all', $captured );
	}

	// T9: palette populated, variables empty -> variables seeded only (spec "one option already populated")
	public function test_handler_seeds_only_variables_when_palette_already_populated(): void {
		$this->mockHandlerAuth();
		$existing_palette = [ [ 'id' => 'existing', 'name' => 'Existing', 'colors' => [] ] ];
		$this->mockBricksOptions( [
			'bricks_color_palette'    => $existing_palette,
			'bricks_global_variables' => [],
		] );

		$calls = [];
		Monkey\Functions\expect( 'update_option' )->once()->andReturnUsing( function ( $name, $value ) use ( &$calls ) {
			$calls[] = [ $name, $value ];
			return true;
		} );

		$captured = null;
		$this->captureRedirect( $captured );

		try {
			playbrick_seed_bricks_tokens( true );
		} catch ( \RuntimeException $e ) {
			// Expected.
		}

		$this->assertSame( [ 'bricks_global_variables', playbrick_default_bricks_global_variables() ], $calls[0] );
		$this->assertSame( 'options-general.php?page=playbrick&bricks_tokens_seeded=variables', $captured );
	}

	// T9b (variables populated, palette empty -> palette seeded only)
	public function test_handler_seeds_only_palette_when_variables_already_populated(): void {
		$this->mockHandlerAuth();
		$existing_variables = [ [ 'id' => 'existing', 'name' => 'space-existing', 'value' => '1rem' ] ];
		$this->mockBricksOptions( [
			'bricks_color_palette'    => [],
			'bricks_global_variables' => $existing_variables,
		] );

		$calls = [];
		Monkey\Functions\expect( 'update_option' )->once()->andReturnUsing( function ( $name, $value ) use ( &$calls ) {
			$calls[] = [ $name, $value ];
			return true;
		} );

		$captured = null;
		$this->captureRedirect( $captured );

		try {
			playbrick_seed_bricks_tokens( true );
		} catch ( \RuntimeException $e ) {
			// Expected.
		}

		$this->assertSame( [ 'bricks_color_palette', playbrick_default_bricks_color_palette() ], $calls[0] );
		$this->assertSame( 'options-general.php?page=playbrick&bricks_tokens_seeded=palette', $captured );
	}

	// T10: both populated -> no writes, redirect ...=none (SC3)
	public function test_handler_writes_nothing_when_both_options_already_populated(): void {
		$this->mockHandlerAuth();
		$this->mockBricksOptions( [
			'bricks_color_palette'    => [ [ 'id' => 'existing', 'name' => 'Existing', 'colors' => [] ] ],
			'bricks_global_variables' => [ [ 'id' => 'existing', 'name' => 'space-existing', 'value' => '1rem' ] ],
		] );

		Monkey\Functions\expect( 'update_option' )->never();

		$captured = null;
		$this->captureRedirect( $captured );

		try {
			playbrick_seed_bricks_tokens( true );
		} catch ( \RuntimeException $e ) {
			// Expected.
		}

		$this->assertSame( 'options-general.php?page=playbrick&bricks_tokens_seeded=none', $captured );
	}

	// T13: update_option returns false -> redirect error=bricks_seed_write_failed (A6)
	public function test_handler_redirects_with_error_when_write_fails(): void {
		$this->mockHandlerAuth();
		$this->mockBricksOptions( [
			'bricks_color_palette'    => [],
			'bricks_global_variables' => [],
		] );

		Monkey\Functions\expect( 'update_option' )->once()->andReturn( false );

		$captured = null;
		$this->captureRedirect( $captured );

		try {
			playbrick_seed_bricks_tokens( true );
		} catch ( \RuntimeException $e ) {
			// Expected.
		}

		$this->assertSame( 'options-general.php?page=playbrick&error=bricks_seed_write_failed', $captured );
	}

	// --- Settings UI (Phase 4) --------------------------------------------------

	public function test_settings_page_renders_seed_bricks_tokens_form(): void {
		Monkey\Functions\when( 'get_option' )->justReturn( [] );
		Monkey\Functions\when( 'esc_html' )->returnArg( 1 );
		Monkey\Functions\when( 'esc_attr' )->returnArg( 1 );
		Monkey\Functions\when( 'selected' )->justReturn( '' );
		Monkey\Functions\when( 'wp_upload_dir' )->justReturn( [ 'basedir' => sys_get_temp_dir() . '/uploads' ] );
		Monkey\Functions\when( 'admin_url' )->returnArg( 1 );
		Monkey\Functions\when( 'wp_nonce_field' )->alias( function ( $action = -1 ) {
			echo '<input type="hidden" name="_wpnonce_' . $action . '" value="test" />';
		} );
		Monkey\Functions\when( 'submit_button' )->alias( function ( $text = 'Save Changes' ) {
			echo '<button>' . $text . '</button>';
		} );

		ob_start();
		playbrick_settings_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="_wpnonce_playbrick_seed_bricks_tokens"', $html );
		$this->assertStringContainsString( 'name="action" value="playbrick_seed_bricks_tokens"', $html );
	}

	public function test_settings_page_shows_typed_seed_notices(): void {
		Monkey\Functions\when( 'get_option' )->justReturn( [] );
		Monkey\Functions\when( 'esc_html' )->returnArg( 1 );
		Monkey\Functions\when( 'esc_attr' )->returnArg( 1 );
		Monkey\Functions\when( 'selected' )->justReturn( '' );
		Monkey\Functions\when( 'wp_upload_dir' )->justReturn( [ 'basedir' => sys_get_temp_dir() . '/uploads' ] );
		Monkey\Functions\when( 'admin_url' )->returnArg( 1 );
		Monkey\Functions\when( 'wp_nonce_field' )->justReturn( '' );
		Monkey\Functions\when( 'submit_button' )->justReturn( '' );

		$_GET['bricks_tokens_seeded'] = 'variables';
		ob_start();
		playbrick_settings_page();
		$html = ob_get_clean();
		unset( $_GET['bricks_tokens_seeded'] );

		$this->assertStringContainsString( 'palette skipped', $html );
		$this->assertStringContainsString( 'variables seeded', $html );

		$_GET['error'] = 'bricks_missing';
		ob_start();
		playbrick_settings_page();
		$html = ob_get_clean();
		unset( $_GET['error'] );

		$this->assertStringContainsString( 'Bricks Builder does not appear to be active', $html );
	}
}
