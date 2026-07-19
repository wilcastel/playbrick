<?php
namespace PlayBrick\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class EnqueueStrategyTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_defaults_to_plugin_when_option_is_missing(): void {
		Monkey\Functions\when( 'get_option' )->justReturn( [] );

		$this->assertSame( 'plugin', playbrick_get_enqueue_strategy() );
		$this->assertFalse( playbrick_is_child_theme_strategy() );
	}

	public function test_recognizes_child_theme_strategy(): void {
		Monkey\Functions\when( 'get_option' )->justReturn( [ 'enqueue_strategy' => 'child_theme' ] );

		$this->assertSame( 'child_theme', playbrick_get_enqueue_strategy() );
		$this->assertTrue( playbrick_is_child_theme_strategy() );
	}

	public function test_invalid_strategy_falls_back_to_plugin(): void {
		Monkey\Functions\when( 'get_option' )->justReturn( [ 'enqueue_strategy' => 'invalid' ] );

		$this->assertSame( 'plugin', playbrick_get_enqueue_strategy() );
		$this->assertFalse( playbrick_is_child_theme_strategy() );
	}

	public function test_can_read_strategy_from_passed_array(): void {
		$this->assertSame( 'child_theme', playbrick_get_enqueue_strategy( [ 'enqueue_strategy' => 'child_theme' ] ) );
		$this->assertTrue( playbrick_is_child_theme_strategy( [ 'enqueue_strategy' => 'child_theme' ] ) );
	}
}
