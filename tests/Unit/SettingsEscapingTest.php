<?php
/**
 * Tests that settings-UI output is escaped.
 *
 * Regression coverage for audit item R1. Two sites echoed unescaped values.
 *
 * The escaping function matters and differs by context: the nav-tab slug goes
 * into an HTML attribute (esc_attr), the tab title into text (esc_html), and
 * setting descriptions into markup that intentionally contains code samples and
 * links (wp_kses_post - NOT esc_html, which would render them literally).
 *
 * @package footnotes
 */

declare(strict_types=1);

namespace footnotes\tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Asserts the settings UI escapes what it outputs.
 */
final class SettingsEscapingTest extends TestCase {

	/**
	 * Reads a plugin file.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @return string Contents.
	 */
	private function source( string $relative ): string {
		$path = dirname( __DIR__, 2 ) . '/' . $relative;

		$this->assertFileExists( $path );

		return (string) file_get_contents( $path );
	}

	/**
	 * The nav-tab slug must be escaped as an attribute.
	 *
	 * @return void
	 */
	public function test_section_slug_is_escaped_as_an_attribute(): void {
		$source = $this->source( 'admin/layout/class-engine.php' );

		$this->assertStringContainsString(
			'esc_attr( $section_slug )',
			$source,
			'The section slug is written into an href attribute and must use esc_attr().'
		);
	}

	/**
	 * The nav-tab title must be escaped as text.
	 *
	 * @return void
	 */
	public function test_section_title_is_escaped_as_text(): void {
		$source = $this->source( 'admin/layout/class-engine.php' );

		$this->assertStringContainsString(
			'esc_html( $section->get_title() )',
			$source,
			'The section title is output as text and must use esc_html().'
		);
	}

	/**
	 * Setting descriptions must be escaped with wp_kses_post, not esc_html.
	 *
	 * The descriptions contain intentional markup from the partials; esc_html
	 * would break the settings UI by displaying tags literally.
	 *
	 * @return void
	 */
	public function test_descriptions_use_wp_kses_post(): void {
		$source = $this->source( 'admin/layout/class-settings-page.php' );

		$this->assertStringContainsString(
			"wp_kses_post( \$args['description'] )",
			$source,
			'Setting descriptions contain intentional markup and must use wp_kses_post().'
		);
	}

	/**
	 * wp_kses_post must preserve the markup the descriptions rely on.
	 *
	 * @return void
	 */
	public function test_wp_kses_post_preserves_intended_markup(): void {
		$input  = 'Use <code>((note))</code> and see <a href="#">the guide</a>.';
		$result = wp_kses_post( $input );

		$this->assertStringContainsString( '<code>', $result );
		$this->assertStringContainsString( '<a href="#">', $result );
	}
}
