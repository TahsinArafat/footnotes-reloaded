<?php
/**
 * Tests that directly-loadable PHP files guard against direct access.
 *
 * Regression coverage for audit item R5. Class files under includes/, admin/
 * and public/ are loaded by the plugin, never served directly, but reviewers
 * expect an explicit guard. This also prevents undefined-function errors if a
 * file is somehow requested directly.
 *
 * @package footnotes
 */

declare(strict_types=1);

namespace footnotes\tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Asserts every shipped source file has a direct-access guard.
 */
final class DirectAccessGuardTest extends TestCase {

	/**
	 * Directories containing plugin source.
	 *
	 * @var string[]
	 */
	private const SOURCE_DIRS = array( 'includes', 'admin', 'public' );

	/**
	 * Every PHP source file must define-check before running.
	 *
	 * @return void
	 */
	public function test_every_source_file_has_a_direct_access_guard(): void {
		$root      = dirname( __DIR__, 2 );
		$unguarded = array();

		foreach ( self::SOURCE_DIRS as $dir ) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $root . '/' . $dir )
			);

			foreach ( $iterator as $file ) {
				if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
					continue;
				}

				// The "silence is golden" stubs output nothing and need no guard.
				$source = (string) file_get_contents( $file->getPathname() );
				if ( false !== strpos( $source, 'Silence is golden' ) ) {
					continue;
				}

				// Only the leading portion is inspected: the guard must come
				// before any executable code.
				$head = substr( $source, 0, 2000 );

				if ( ! preg_match( '/defined\(\s*[\'"](?:ABSPATH|WPINC|WP_UNINSTALL_PLUGIN)[\'"]\s*\)/', $head ) ) {
					$unguarded[] = str_replace( $root . '/', '', $file->getPathname() );
				}
			}
		}

		$this->assertSame(
			array(),
			$unguarded,
			'These files lack a direct-access guard: ' . implode( ', ', $unguarded )
		);
	}

	/**
	 * uninstall.php must guard on WP_UNINSTALL_PLUGIN specifically.
	 *
	 * It is invoked directly by WordPress during uninstall, so it needs the
	 * correct constant, not the generic ABSPATH check.
	 *
	 * @return void
	 */
	public function test_uninstall_guards_on_the_uninstall_constant(): void {
		$source = (string) file_get_contents( dirname( __DIR__, 2 ) . '/uninstall.php' );

		$this->assertStringContainsString(
			'WP_UNINSTALL_PLUGIN',
			$source,
			'uninstall.php must exit unless WP_UNINSTALL_PLUGIN is defined.'
		);
	}
}
