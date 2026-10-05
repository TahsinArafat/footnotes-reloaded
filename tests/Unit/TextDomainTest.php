<?php
/**
 * Tests that translation calls use the plugin slug as their text domain.
 *
 * WordPress.org requires a plugin's text domain to match its slug, otherwise
 * translations are never loaded from translate.wordpress.org.
 *
 * Equally important: the domain change must NOT touch identifiers that are
 * stored as data. Option names and setting keys must keep their original
 * spelling, or every existing user's settings would be silently orphaned.
 *
 * @package footnotes
 */

declare(strict_types=1);

namespace footnotes\tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Asserts the translation domain matches the slug without changing stored keys.
 */
final class TextDomainTest extends TestCase {

	/**
	 * The domain translations are registered under.
	 *
	 * @var string
	 */
	private const DOMAIN = 'footnotes-reloaded';

	/**
	 * Stored identifiers that must keep their original names.
	 *
	 * @var string[]
	 */
	private const STORED_IDENTIFIERS = array(
		'footnotes_storage',
		'footnotes_storage_custom',
		'footnotes_storage_expert',
		'footnotes_storage_custom_css',
	);

	/**
	 * No translation call may still use the old `footnotes` domain.
	 *
	 * @return void
	 */
	public function test_no_translation_call_uses_the_old_domain(): void {
		$offenders = array();

		foreach ( $this->source_files() as $path ) {
			$contents = (string) file_get_contents( $path );

			if ( preg_match(
				'/\b(?:esc_html__|esc_attr__|esc_html_e|esc_attr_e|__|_e|_x|_n|_nx)\(\s*[^,)]+,\s*\'footnotes\'/',
				$contents
			) ) {
				$offenders[] = basename( $path );
			}
		}

		$this->assertSame(
			array(),
			$offenders,
			'These files still pass the old text domain: ' . implode( ', ', $offenders )
		);
	}

	/**
	 * The plugin header must declare the domain matching the slug.
	 *
	 * @return void
	 */
	public function test_header_declares_the_slug_as_the_domain(): void {
		$header = (string) file_get_contents( dirname( __DIR__, 2 ) . '/footnotes.php' );

		$this->assertMatchesRegularExpression(
			'/^\s*\*\s*Text Domain:\s*' . preg_quote( self::DOMAIN, '/' ) . '\s*$/m',
			$header,
			'The plugin header Text Domain must match the plugin slug.'
		);
	}

	/**
	 * The i18n constant must agree with the header.
	 *
	 * @return void
	 */
	public function test_i18n_constant_matches_the_domain(): void {
		$source = (string) file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-i18n.php' );

		$this->assertStringContainsString(
			"'" . self::DOMAIN . "'",
			$source,
			'i18n::TEXT_DOMAIN must match the declared text domain.'
		);
	}

	/**
	 * Stored identifiers must keep their original names.
	 *
	 * Renaming these would break every existing installation's settings.
	 *
	 * @return void
	 */
	public function test_stored_identifiers_are_unchanged(): void {
		$settings = (string) file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-settings.php' );

		foreach ( self::STORED_IDENTIFIERS as $identifier ) {
			$this->assertStringContainsString(
				$identifier,
				$settings,
				"Stored option '{$identifier}' must not be renamed."
			);
		}
	}

	/**
	 * Setting keys must keep their original prefix and wording.
	 *
	 * @return void
	 */
	public function test_setting_keys_are_unchanged(): void {
		$found = 0;

		foreach ( $this->source_files() as $path ) {
			$found += (int) preg_match_all(
				"/'footnote_inputfield_[a-z0-9_]+'/",
				(string) file_get_contents( $path )
			);
		}

		$this->assertGreaterThan(
			0,
			$found,
			'Setting keys should still use the footnote_inputfield_ prefix.'
		);
	}

	/**
	 * Returns the shipped PHP source files.
	 *
	 * @return string[] Absolute paths.
	 */
	private function source_files(): array {
		$root  = dirname( __DIR__, 2 );
		$paths = array();

		foreach ( array( 'includes', 'admin', 'public' ) as $dir ) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $root . '/' . $dir )
			);

			foreach ( $iterator as $file ) {
				if ( $file->isFile() && 'php' === $file->getExtension() ) {
					$paths[] = $file->getPathname();
				}
			}
		}

		return $paths;
	}
}
