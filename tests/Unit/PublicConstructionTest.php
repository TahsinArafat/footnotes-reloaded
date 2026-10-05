<?php
/**
 * Tests that the public-side is constructed without translating.
 *
 * Regression coverage for TRIAGE-001. `General::load_dependencies()` runs during
 * `plugins_loaded`. Anything it constructs must not translate, because WordPress
 * 6.7 emits `_load_textdomain_just_in_time` for translation before `init` —
 * which broke the entire admin page.
 *
 * The Reference Container widget historically resolved a translated description
 * in its constructor. It is now built lazily on `widgets_init` (after `init`),
 * inside `General::register_widgets()`.
 *
 * @package footnotes
 */

declare(strict_types=1);

namespace footnotes\tests\Unit;

use footnotes\general\General;
use footnotes\includes\Settings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Asserts public-side construction performs no translation.
 */
final class PublicConstructionTest extends TestCase {

	/**
	 * Resets recorded WordPress state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		footnotes_test_reset_wp_state();

		require_once dirname( __DIR__, 2 ) . '/includes/class-config.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-settings.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-convert.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-template.php';
		require_once dirname( __DIR__, 2 ) . '/public/class-parser.php';
		require_once dirname( __DIR__, 2 ) . '/public/widget/class-base.php';
		require_once dirname( __DIR__, 2 ) . '/public/widget/class-reference-container.php';
		require_once dirname( __DIR__, 2 ) . '/public/class-general.php';
	}

	/**
	 * Loading the public-side dependencies must not translate.
	 *
	 * @return void
	 */
	public function test_load_dependencies_does_not_translate(): void {
		$general = $this->make_general();

		$method = new ReflectionMethod( General::class, 'load_dependencies' );
		$method->invoke( $general );

		$this->assertSame(
			array(),
			$GLOBALS['__translation_calls'],
			'load_dependencies() must not translate; it runs on `plugins_loaded`, before `init`.'
		);
	}

	/**
	 * The widget is not constructed by load_dependencies().
	 *
	 * @return void
	 */
	public function test_widget_is_not_constructed_eagerly(): void {
		$general = $this->make_general();

		$method = new ReflectionMethod( General::class, 'load_dependencies' );
		$method->invoke( $general );

		$reflection = new ReflectionClass( $general );
		$prop       = $reflection->getProperty( 'reference_container_widget' );
		$widget     = $prop->getValue( $general );

		$this->assertNull(
			$widget,
			'The widget must be built lazily on `widgets_init`, not during `plugins_loaded`.'
		);
	}

	/**
	 * Builds a General instance without running its constructor.
	 *
	 * @return General Instance with only the fields register_widgets() needs.
	 */
	private function make_general(): General {
		$reflection = new ReflectionClass( General::class );
		$general    = $reflection->newInstanceWithoutConstructor();

		$reflection->getProperty( 'plugin_name' )->setValue( $general, 'footnotes' );

		// Build a real Settings object so all settings groups are loaded, as
		// they are in production.
		$settings = Settings::instance();
		$reflection->getProperty( 'settings' )->setValue( $general, $settings );

		return $general;
	}
}
