<?php
namespace PlayBrick\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class BuilderPanelTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		$_GET = [];
		Monkey\tearDown();
		parent::tearDown();
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
		$this->assertContains( '16 / 9', $completions['values']['aspect-ratio'] );
		$this->assertContains( 'balance', $completions['values']['text-wrap'] );
		$this->assertContains( 'flex', $completions['values']['display'] );
	}
}
