<?php
/**
 * Tests for the plugin's locale / textdomain loading timing.
 *
 * Regression coverage for TRIAGE-001: WordPress 6.7 emits
 * `_load_textdomain_just_in_time was called incorrectly` when a plugin loads
 * its textdomain before the `init` action.
 *
 * @package footnotes
 */

declare(strict_types=1);

namespace footnotes\tests\Unit;

use footnotes\includes\Core;
use footnotes\includes\Loader;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Asserts the textdomain is hooked to a sufficiently late action.
 */
final class I18nTimingTest extends TestCase {

	/**
	 * Resets recorded WordPress state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		footnotes_test_reset_wp_state();
	}

	/**
	 * The locale hooks must not target `plugins_loaded`.
	 *
	 * `plugins_loaded` fires before `init`; hooking there is what triggers the
	 * WordPress 6.7+ just-in-time notice.
	 *
	 * @return void
	 */
	public function test_textdomain_is_not_hooked_to_plugins_loaded(): void {
		$hooks = $this->collect_locale_hooks();

		$this->assertNotContains(
			'plugins_loaded',
			$hooks,
			'The textdomain must not load on `plugins_loaded`; WordPress 6.7+ requires `init` or later.'
		);
	}

	/**
	 * The locale hooks should target `init`.
	 *
	 * @return void
	 */
	public function test_textdomain_is_hooked_to_init(): void {
		$hooks = $this->collect_locale_hooks();

		$this->assertContains(
			'init',
			$hooks,
			'The textdomain should be loaded on the `init` action.'
		);
	}

	/**
	 * Firing `init` should load the `footnotes` textdomain.
	 *
	 * @return void
	 */
	public function test_firing_init_loads_the_footnotes_textdomain(): void {
		$loader = $this->populate_loader();

		// Flush the collected hooks into the shim, then fire `init`.
		$loader->run();
		do_action( 'init' );

		$domains = array_column( $GLOBALS['__loaded_textdomains'], 'domain' );

		$this->assertContains(
			'footnotes',
			$domains,
			'Firing `init` should load the `footnotes` textdomain.'
		);
	}

	/**
	 * Invokes the private Core::set_locale() and returns the Loader it populated.
	 *
	 * @return Loader Populated loader.
	 */
	private function populate_loader(): Loader {
		require_once dirname( __DIR__, 2 ) . '/includes/class-loader.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-i18n.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-core.php';

		$reflection = new ReflectionClass( Core::class );
		$core       = $reflection->newInstanceWithoutConstructor();

		$loader = new Loader();
		$prop   = $reflection->getProperty( 'loader' );
		$prop->setValue( $core, $loader );

		$method = new ReflectionMethod( Core::class, 'set_locale' );
		$method->invoke( $core );

		return $loader;
	}

	/**
	 * Returns the hook names Core::set_locale() registered.
	 *
	 * @return string[] Hook names.
	 */
	private function collect_locale_hooks(): array {
		$loader     = $this->populate_loader();
		$reflection = new ReflectionClass( $loader );
		$prop       = $reflection->getProperty( 'actions' );
		$actions    = $prop->getValue( $loader );

		return array_values( array_map( static fn( array $a ): string => $a['hook'], $actions ) );
	}

	/**
	 * Populates the loader, then points it at the global hook functions so
	 * run() executes against the test shim.
	 *
	 * @return Loader Populated loader ready to run().
	 */
}
