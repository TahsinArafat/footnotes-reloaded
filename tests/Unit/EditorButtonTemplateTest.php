<?php
/**
 * Tests for the Classic Editor plain-text button template.
 *
 * Regression coverage for TRIAGE-007 (upstream #15 / #193). The template guarded
 * its QTags registration with `if ( QTags )`. Referencing an undeclared
 * identifier throws a ReferenceError, so that guard cannot protect against
 * QTags being absent — it produces the very error it is meant to prevent.
 *
 * @package footnotes
 */

declare(strict_types=1);

namespace footnotes\tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Asserts the editor button template guards QTags safely.
 */
final class EditorButtonTemplateTest extends TestCase {

	/**
	 * The template source.
	 *
	 * @var string
	 */
	private string $source;

	/**
	 * Loads the template source.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$path = dirname( __DIR__, 2 ) . '/admin/partials/editor-button.html';

		$this->assertFileExists( $path, 'The editor button template should exist.' );

		$this->source = (string) file_get_contents( $path );
	}

	/**
	 * The QTags guard must use `typeof`, which is safe for undeclared identifiers.
	 *
	 * @return void
	 */
	public function test_qtags_guard_uses_typeof(): void {
		$this->assertStringContainsString(
			"'undefined' !== typeof QTags",
			$this->source,
			'The QTags guard must use `typeof QTags`, otherwise an absent QTags throws a ReferenceError.'
		);
	}
}
