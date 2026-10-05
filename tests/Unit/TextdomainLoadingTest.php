<?php
/**
 * Tests that the plugin does not call load_plugin_textdomain().
 *
 * WordPress.org plugin guidelines prohibit calling load_plugin_textdomain()
 * for hosted plugins: since WordPress 4.6, translations for a plugin's own text
 * domain load automatically from translate.wordpress.org. Removing the call also
 * makes the "textdomain loaded too early" bug class (TRIAGE-001) impossible.
 *
 * @package footnotes
 */

declare(strict_types=1);

namespace footnotes\tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Asserts the plugin leaves textdomain loading to WordPress.
 */
final class TextdomainLoadingTest extends TestCase {

	/**
	 * Source directories that make up the plugin.
	 *
	 * @var string[]
	 */
	private const SOURCE_DIRS = array( 'includes', 'admin', 'public' );

	/**
	 * No shipped source file may call load_plugin_textdomain().
	 *
	 * @return void
	 */
	public function test_no_source_file_calls_load_plugin_textdomain(): void {
		$root    = dirname( __DIR__, 2 );
		$offends = array();

		foreach ( self::SOURCE_DIRS as $dir ) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $root . '/' . $dir )
			);

			foreach ( $iterator as $file ) {
				if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
					continue;
				}

				$source   = (string) file_get_contents( $file->getPathname() );
				$stripped = preg_replace( '#/\*.*?\*/#s', '', $source );
				$stripped = preg_replace( '#^\s*//.*$#m', '', (string) $stripped );

				if ( false !== strpos( (string) $stripped, 'load_plugin_textdomain(' ) ) {
					$offends[] = str_replace( $root . '/', '', $file->getPathname() );
				}
			}
		}

		$this->assertSame(
			array(),
			$offends,
			'WordPress.org prohibits load_plugin_textdomain(). Offending files: '
			. implode( ', ', $offends )
		);
	}

	/**
	 * The plugin must still declare a text domain, so WordPress can load
	 * translations for it automatically.
	 *
	 * @return void
	 */
	public function test_plugin_header_declares_a_text_domain(): void {
		$root = dirname( __DIR__, 2 );

		$this->assertMatchesRegularExpression(
			'/^\s*\*\s*Text Domain:\s*\S+/m',
			(string) file_get_contents( $root . '/footnotes.php' ),
			'The plugin header must declare a Text Domain.'
		);
	}
}
