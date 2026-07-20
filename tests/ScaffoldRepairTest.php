<?php
namespace PlayBrick\Tests;

use PHPUnit\Framework\TestCase;

class ScaffoldRepairTest extends TestCase {

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

	protected function tearDown(): void {
		$this->rmdir( sys_get_temp_dir() . '/playbrick-scaffold-repair' );
		parent::tearDown();
	}

	public function test_detects_tailwind_scaffold_from_package_json(): void {
		$playground = sys_get_temp_dir() . '/playbrick-scaffold-repair/detect-tailwind';
		mkdir( $playground, 0777, true );
		file_put_contents( $playground . '/package.json', '{"devDependencies":{"@tailwindcss/postcss":"^4.0.0"}}' );

		$this->assertSame( 'tailwind', playbrick_detect_scaffold_mode( $playground ) );
	}

	public function test_detects_mismatch_between_settings_and_playground(): void {
		$playground = sys_get_temp_dir() . '/playbrick-scaffold-repair/detect-mismatch';
		mkdir( $playground, 0777, true );
		file_put_contents( $playground . '/package.json', '{"devDependencies":{"postcss-import":"^16.0.0"}}' );

		$status = playbrick_scaffold_status( [
			'scaffold_mode'   => 'tailwind',
			'playground_path' => $playground,
		] );

		$this->assertSame( 'tailwind', $status['expected'] );
		$this->assertSame( 'classic', $status['actual'] );
		$this->assertFalse( $status['matches'] );
	}

	public function test_repair_overwrites_scaffold_files_but_preserves_user_bricks(): void {
		$playground = sys_get_temp_dir() . '/playbrick-scaffold-repair/repair-tailwind';
		mkdir( $playground . '/bricks/components', 0777, true );
		file_put_contents( $playground . '/build.js', 'classic-build' );
		file_put_contents( $playground . '/dev.css', 'classic-css' );
		file_put_contents( $playground . '/package.json', '{"devDependencies":{"postcss-import":"^16.0.0"}}' );
		file_put_contents( $playground . '/playbrick.config.js', "module.exports = { mode: 'tailwind' };\n" );
		file_put_contents( $playground . '/bricks/components/card.css', '.card{}' );

		$result = playbrick_repair_scaffold( [
			'scaffold_mode'   => 'tailwind',
			'playground_path' => $playground,
		] );

		$this->assertTrue( $result );
		$this->assertSame( 'tailwind', playbrick_detect_scaffold_mode( $playground ) );
		$this->assertStringContainsString( '@tailwindcss/postcss', file_get_contents( $playground . '/package.json' ) );
		$this->assertStringContainsString( '@import "tailwindcss"', file_get_contents( $playground . '/dev.css' ) );
		$this->assertSame( '.card{}', file_get_contents( $playground . '/bricks/components/card.css' ) );
		$this->assertStringContainsString( "mode: 'tailwind'", file_get_contents( $playground . '/playbrick.config.js' ) );
	}
}
