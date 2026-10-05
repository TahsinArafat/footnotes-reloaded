<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase {
	public function test_phpunit_runs_and_bootstrap_loads(): void {
		$this->assertTrue( function_exists( 'load_plugin_textdomain' ) );
		$this->assertTrue( defined( 'PLUGIN_VERSION' ) );
	}
}
