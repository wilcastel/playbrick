<?php
namespace PlayBrick\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class ChildThemeGenerationTest extends TestCase {

	private function rmdir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $items as $item ) {
			$path = $item->getPathname();
			if ( $item->isDir() ) {
				rmdir( $path );
			} else {
				unlink( $path );
			}
		}
		rmdir( $dir );
	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->rmdir( ABSPATH );

		$theme_dir = ABSPATH . 'wp-content/themes/my-child-theme';
		mkdir( $theme_dir, 0777, true );
		file_put_contents( $theme_dir . '/functions.php', "<?php\n" );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		$this->rmdir( ABSPATH );
		parent::tearDown();
	}

	public function test_generate_handler_creates_standalone_file_and_patches_functions_php(): void {
		$theme_dir = ABSPATH . 'wp-content/themes/my-child-theme';

		Monkey\Functions\when( 'get_option' )->justReturn( [
			'env'              => 'dev',
			'scaffold_mode'    => 'classic',
			'playground_path'  => ABSPATH . 'playground',
			'out_dir'          => sys_get_temp_dir() . '/uploads/assets',
			'enqueue_strategy' => 'child_theme',
			'child_theme'      => $theme_dir,
			'enqueue_file'     => '',
		] );

		Monkey\Functions\when( 'check_admin_referer' )->justReturn( 1 );
		Monkey\Functions\when( 'current_user_can' )->justReturn( true );
		Monkey\Functions\when( 'admin_url' )->returnArg( 1 );

		$captured = null;
		Monkey\Functions\expect( 'wp_redirect' )->once()->andReturnUsing( function ( $url ) use ( &$captured ) {
			$captured = $url;
			throw new \RuntimeException( 'redirect' );
		} );

		try {
			playbrick_generate_child_theme_enqueue();
		} catch ( \RuntimeException $e ) {
			// Expected — wp_redirect was mocked to throw.
		}

		$this->assertSame( 'options-general.php?page=playbrick&child_theme_generated=1', $captured );

		$enqueue_file = $theme_dir . '/includes/playbrick-enqueue.php';
		$this->assertFileExists( $enqueue_file );

		$contents = file_get_contents( $enqueue_file );
		$this->assertStringContainsString( 'PlayBrick child-theme enqueue snippet', $contents );
		$this->assertStringContainsString( "add_action( 'wp_enqueue_scripts',", $contents );
		$this->assertStringContainsString( '}, 9999 );', $contents );
		$this->assertStringContainsString( "( \$settings['enqueue_strategy'] ?? 'plugin' ) !== 'child_theme'", $contents );
		$this->assertStringContainsString( "function_exists( 'playbrick_path_to_url' )", $contents );
		$this->assertStringContainsString( "filemtime( \$css_path )", $contents );

		$functions = file_get_contents( $theme_dir . '/functions.php' );
		$this->assertStringContainsString( "require_once get_stylesheet_directory() . '/includes/playbrick-enqueue.php';", $functions );
	}

	public function test_generate_handler_appends_to_existing_enqueue_file(): void {
		$theme_dir = ABSPATH . 'wp-content/themes/my-child-theme';
		$existing_file = $theme_dir . '/includes/features/enqueue-scripts.php';
		mkdir( dirname( $existing_file ), 0777, true );
		file_put_contents( $existing_file, "<?php\n// theme enqueue\n" );

		Monkey\Functions\when( 'get_option' )->justReturn( [
			'env'              => 'dev',
			'scaffold_mode'    => 'classic',
			'playground_path'  => ABSPATH . 'playground',
			'out_dir'          => sys_get_temp_dir() . '/uploads/assets',
			'enqueue_strategy' => 'child_theme',
			'child_theme'      => $theme_dir,
			'enqueue_file'     => 'includes/features/enqueue-scripts.php',
		] );

		Monkey\Functions\when( 'check_admin_referer' )->justReturn( 1 );
		Monkey\Functions\when( 'current_user_can' )->justReturn( true );
		Monkey\Functions\when( 'admin_url' )->returnArg( 1 );

		Monkey\Functions\expect( 'wp_redirect' )->once()->andReturnUsing( function () {
			throw new \RuntimeException( 'redirect' );
		} );

		try {
			playbrick_generate_child_theme_enqueue();
		} catch ( \RuntimeException $e ) {
			// Expected.
		}

		$this->assertFileExists( $existing_file );
		$contents = file_get_contents( $existing_file );
		$this->assertStringContainsString( '// theme enqueue', $contents );
		$this->assertStringContainsString( 'PlayBrick child-theme enqueue snippet', $contents );
		$this->assertStringContainsString( "add_action( 'wp_enqueue_scripts',", $contents );
	}

	public function test_generate_handler_rejects_enqueue_file_outside_child_theme(): void {
		$theme_dir = ABSPATH . 'wp-content/themes/my-child-theme';
		file_put_contents( ABSPATH . 'outside.php', "<?php\n" );

		Monkey\Functions\when( 'get_option' )->justReturn( [
			'env'              => 'dev',
			'scaffold_mode'    => 'classic',
			'playground_path'  => ABSPATH . 'playground',
			'out_dir'          => sys_get_temp_dir() . '/uploads/assets',
			'enqueue_strategy' => 'child_theme',
			'child_theme'      => $theme_dir,
			'enqueue_file'     => '../../../outside.php',
		] );

		Monkey\Functions\when( 'check_admin_referer' )->justReturn( 1 );
		Monkey\Functions\when( 'current_user_can' )->justReturn( true );
		Monkey\Functions\when( 'admin_url' )->returnArg( 1 );

		$captured = null;
		Monkey\Functions\expect( 'wp_redirect' )->once()->andReturnUsing( function ( $url ) use ( &$captured ) {
			$captured = $url;
			throw new \RuntimeException( 'redirect' );
		} );

		try {
			playbrick_generate_child_theme_enqueue();
		} catch ( \RuntimeException $e ) {
			// Expected.
		}

		$this->assertSame( 'options-general.php?page=playbrick&error=child_theme_file_forbidden', $captured );
		$this->assertSame( "<?php\n", file_get_contents( ABSPATH . 'outside.php' ) );
	}

	public function test_generate_handler_rejects_child_theme_path_outside_themes_directory(): void {
		$forbidden_dir = ABSPATH;

		Monkey\Functions\when( 'get_option' )->justReturn( [
			'env'              => 'dev',
			'scaffold_mode'    => 'classic',
			'playground_path'  => ABSPATH . 'playground',
			'out_dir'          => sys_get_temp_dir() . '/uploads/assets',
			'enqueue_strategy' => 'child_theme',
			'child_theme'      => $forbidden_dir,
			'enqueue_file'     => 'wp-config.php',
		] );

		file_put_contents( ABSPATH . 'wp-config.php', "<?php\n" );
		Monkey\Functions\when( 'check_admin_referer' )->justReturn( 1 );
		Monkey\Functions\when( 'current_user_can' )->justReturn( true );
		Monkey\Functions\when( 'admin_url' )->returnArg( 1 );

		$captured = null;
		Monkey\Functions\expect( 'wp_redirect' )->once()->andReturnUsing( function ( $url ) use ( &$captured ) {
			$captured = $url;
			throw new \RuntimeException( 'redirect' );
		} );

		try {
			playbrick_generate_child_theme_enqueue();
		} catch ( \RuntimeException $e ) {
			// Expected.
		}

		$this->assertSame( 'options-general.php?page=playbrick&error=child_theme_path_forbidden', $captured );
		$this->assertSame( "<?php\n", file_get_contents( ABSPATH . 'wp-config.php' ) );
	}
}
