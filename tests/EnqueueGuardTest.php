<?php
namespace PlayBrick\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class EnqueueGuardTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Monkey\Functions\when( 'get_option' )->justReturn( [
			'env'              => 'dev',
			'scaffold_mode'    => 'classic',
			'playground_path'  => ABSPATH . 'playground',
			'out_dir'          => sys_get_temp_dir() . '/uploads/assets',
			'enqueue_strategy' => 'child_theme',
		] );

		Monkey\Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Monkey\Functions\when( 'wp_upload_dir' )->justReturn( [
			'basedir' => sys_get_temp_dir() . '/uploads',
			'baseurl' => 'https://example.com/wp-content/uploads',
		] );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_plugin_does_not_enqueue_when_child_theme_strategy_is_active(): void {
		Monkey\Functions\expect( 'wp_enqueue_style' )->never();
		Monkey\Functions\expect( 'wp_enqueue_script' )->never();

		playbrick_enqueue_assets();

		$this->assertTrue( true );
	}
}
