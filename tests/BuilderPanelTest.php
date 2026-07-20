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
}
