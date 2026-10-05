<?php
/**
 * Tests that uninstall.php deletes the data the plugin creates.
 *
 * Regression coverage for audit item R4. The plugin stores four option groups
 * and a widget option; uninstalling it previously left all of that behind.
 *
 * These tests *execute* uninstall.php against the WordPress shim and assert on
 * the calls it actually makes, rather than grepping the source. A source check
 * would pass on a mention in a comment or inside an unreachable branch, and
 * would not catch a call the code never makes.
 *
 * @package footnotes
 */

declare(strict_types=1);

namespace footnotes\tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Asserts uninstall removes what the plugin creates, by observation.
 */
final class UninstallTest extends TestCase {

	/**
	 * Option groups the plugin stores.
	 *
	 * @var string[]
	 */
	private const EXPECTED_OPTIONS = array(
		'footnotes_storage',
		'footnotes_storage_custom',
		'footnotes_storage_expert',
		'footnotes_storage_custom_css',
		'widget_footnotes_widget',
	);

	/**
	 * Resets recorded state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		footnotes_test_reset_wp_state();
	}

	/**
	 * Running uninstall.php deletes every option the plugin creates.
	 *
	 * @return void
	 */
	public function test_uninstall_deletes_every_plugin_option(): void {
		$this->seed_plugin_options();
		$this->run_uninstall();

		$deleted = $GLOBALS['__deleted_options'];

		foreach ( self::EXPECTED_OPTIONS as $option ) {
			$this->assertContains(
				$option,
				$deleted,
				"uninstall.php did not call delete_option() for '{$option}'."
			);
		}
	}

	/**
	 * The options are actually gone from the store afterwards.
	 *
	 * @return void
	 */
	public function test_uninstall_leaves_no_plugin_options_behind(): void {
		$this->seed_plugin_options();
		$this->run_uninstall();

		foreach ( self::EXPECTED_OPTIONS as $option ) {
			$this->assertFalse(
				get_option( $option, false ),
				"'{$option}' still exists after uninstall."
			);
		}
	}

	/**
	 * Options belonging to other plugins are left alone.
	 *
	 * An uninstall script deleting unrelated options would be a serious bug.
	 *
	 * @return void
	 */
	public function test_uninstall_does_not_touch_unrelated_options(): void {
		$this->seed_plugin_options();
		update_option( 'some_other_plugin_setting', 'keep me' );

		$this->run_uninstall();

		$this->assertSame(
			'keep me',
			get_option( 'some_other_plugin_setting', false ),
			'uninstall.php must not delete options it does not own.'
		);
		$this->assertNotContains(
			'some_other_plugin_setting',
			$GLOBALS['__deleted_options'],
			'uninstall.php called delete_option() for an option it does not own.'
		);
	}

	/**
	 * uninstall.php must refuse to run outside the uninstall flow.
	 *
	 * Run in a subprocess: WP_UNINSTALL_PLUGIN is a constant, so once any other
	 * test in this process has defined it, the guard cannot be exercised again.
	 *
	 * The fixture reports its outcome through an exit code, so a crash cannot be
	 * mistaken for a successful guard:
	 *   0   guarded correctly and exited
	 *   42  reached the end of uninstall.php, so the guard failed
	 *   1+  crashed
	 *
	 * @return void
	 */
	public function test_uninstall_does_nothing_without_the_uninstall_constant(): void {
		$script = dirname( __DIR__, 2 ) . '/tests/fixtures/uninstall-without-constant.php';

		$this->assertFileExists( $script );

		$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $script ) . ' 2>&1';
		$output  = array();
		$status  = 0;

		exec( $command, $output, $status );

		$output_text = implode( "\n", $output );

		$this->assertSame(
			0,
			$status,
			'uninstall.php must exit cleanly when WP_UNINSTALL_PLUGIN is undefined. '
			. 'Exit 42 means the guard failed; any other non-zero code means it crashed. Output: '
			. $output_text
		);

		$this->assertStringNotContainsString(
			'DELETED OPTION:',
			$output_text,
			'uninstall.php deleted something without WP_UNINSTALL_PLUGIN being defined.'
		);
	}

	/**
	 * Seeds the options the plugin would have created.
	 *
	 * @return void
	 */
	private function seed_plugin_options(): void {
		foreach ( self::EXPECTED_OPTIONS as $option ) {
			update_option( $option, array( 'seeded' => true ) );
		}
	}

	/**
	 * Includes uninstall.php with the uninstall constant defined.
	 *
	 * @return void
	 */
	private function run_uninstall(): void {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'footnotes/footnotes.php' );
		}

		require dirname( __DIR__, 2 ) . '/uninstall.php';
	}
}
