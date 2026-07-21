<?php
namespace PlayBrick\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class BuilderPanelTest extends TestCase {
	private array $temp_dirs = [];

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		$_GET = [];
		foreach ( $this->temp_dirs as $dir ) {
			$this->remove_dir( $dir );
		}
		$this->temp_dirs = [];
		Monkey\tearDown();
		parent::tearDown();
	}

	private function temp_playground(): string {
		$dir = sys_get_temp_dir() . '/playbrick-builder-panel-' . uniqid( '', true );
		mkdir( $dir . '/.playbrick', 0777, true );
		$this->temp_dirs[] = $dir;

		return $dir;
	}

	private function remove_dir( string $dir ): void {
		if ( ! is_dir( $dir ) ) return;

		$items = scandir( $dir );
		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' ) continue;

			$path = $dir . '/' . $item;
			is_dir( $path ) ? $this->remove_dir( $path ) : unlink( $path );
		}

		rmdir( $dir );
	}

	public function test_builder_panel_is_registered_in_footer(): void {
		$this->assertSame( 100, has_action( 'wp_footer', 'playbrick_builder_panel_output' ) );
	}

	public function test_builder_panel_request_requires_bricks_run_and_permission(): void {
		$_GET['bricks'] = 'run';

		Monkey\Functions\when( 'is_admin' )->justReturn( false );
		Monkey\Functions\when( 'current_user_can' )->justReturn( true );

		$this->assertTrue( playbrick_is_bricks_builder_request() );
	}

	public function test_builder_panel_request_rejects_non_builder_page(): void {
		Monkey\Functions\when( 'is_admin' )->justReturn( false );
		Monkey\Functions\when( 'current_user_can' )->justReturn( true );

		$this->assertFalse( playbrick_is_bricks_builder_request() );
	}

	public function test_css_completions_are_loaded_from_json(): void {
		$completions = playbrick_builder_panel_css_completions();

		$this->assertContains( 'border-radius', $completions['properties'] );
		$this->assertContains( 'background-position', $completions['properties'] );
		$this->assertContains( 'background-blend-mode', $completions['properties'] );
		$this->assertContains( 'background-attachment', $completions['properties'] );
		$this->assertContains( 'background-repeat', $completions['properties'] );
		$this->assertContains( 'background-size', $completions['properties'] );
		$this->assertNotContains( 'background-video', $completions['properties'] );
		$this->assertContains( 'aspect-ratio', $completions['properties'] );
		$this->assertContains( 'font-variation-settings', $completions['properties'] );
		$this->assertContains( 'white-space', $completions['properties'] );
		$this->assertContains( 'text-wrap', $completions['properties'] );
		$this->assertContains( 'text-shadow', $completions['properties'] );
		$this->assertNotContains( 'masonry-layout', $completions['properties'] );
		$this->assertContains( 'center center', $completions['values']['background-position'] );
		$this->assertContains( 'cover', $completions['values']['background-size'] );
		$this->assertContains( 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', $completions['values']['background-image'] );
		$this->assertContains( '16 / 9', $completions['values']['aspect-ratio'] );
		$this->assertContains( 'balance', $completions['values']['text-wrap'] );
		$this->assertContains( 'flex', $completions['values']['display'] );
		$this->assertContains( 'bg-red-500', $completions['tailwindUtilities'] );
		$this->assertContains( 'md:flex', $completions['tailwindUtilities'] );
		$this->assertContains( 'text-5xl', $completions['tailwindUtilities'] );
		$this->assertContains( 'font-bold', $completions['tailwindUtilities'] );
	}

	public function test_css_completions_merge_dynamic_tailwind_utilities(): void {
		$playground = $this->temp_playground();
		file_put_contents(
			$playground . '/.playbrick/tailwind-utilities.json',
			json_encode(
				[
					'tailwindUtilities' => [
						'bg-brand-amber',
						'text-brand-amber',
						'bg-red-500',
						'',
						123,
						'bg-brand-amber',
					],
				]
			)
		);

		Monkey\Functions\when( 'get_option' )->justReturn( [ 'playground_path' => $playground ] );

		$utilities = playbrick_builder_panel_css_completions()['tailwindUtilities'];

		$this->assertContains( 'bg-brand-amber', $utilities );
		$this->assertContains( 'text-brand-amber', $utilities );
		$this->assertContains( 'bg-red-500', $utilities );
		$this->assertSame( 1, count( array_keys( $utilities, 'bg-brand-amber', true ) ) );
		$this->assertSame( 1, count( array_keys( $utilities, 'bg-red-500', true ) ) );
		$this->assertNotContains( '', $utilities );
		$this->assertFalse( in_array( 123, $utilities, true ) );
	}

	public function test_css_completions_ignore_missing_or_invalid_dynamic_json(): void {
		$playground = $this->temp_playground();
		Monkey\Functions\when( 'get_option' )->justReturn( [ 'playground_path' => $playground ] );

		$missing = playbrick_builder_panel_css_completions();
		$this->assertContains( 'bg-red-500', $missing['tailwindUtilities'] );

		file_put_contents( $playground . '/.playbrick/tailwind-utilities.json', '{bad json' );

		$invalid = playbrick_builder_panel_css_completions();
		$this->assertContains( 'bg-red-500', $invalid['tailwindUtilities'] );
	}
}
