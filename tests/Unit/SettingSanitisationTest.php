<?php
/**
 * Tests that settings are sanitised according to their declared type.
 *
 * Regression coverage for audit items R2 (settings save had no nonce
 * verification) and R3 (raw $_POST values were written to the options table).
 *
 * `wp_unslash()` is not sanitisation. Each setting declares a type, and stored
 * values must conform to it.
 *
 * @package footnotes
 */

declare(strict_types=1);

namespace footnotes\tests\Unit;

use footnotes\includes\settings\Setting;
use PHPUnit\Framework\TestCase;

/**
 * Asserts per-type sanitisation of setting values.
 */
final class SettingSanitisationTest extends TestCase {

	/**
	 * Loads the Setting class.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		footnotes_test_reset_wp_state();

		require_once dirname( __DIR__, 2 ) . '/includes/class-config.php';
		require_once dirname( __DIR__, 2 ) . '/includes/settings/class-setting.php';
	}

	/**
	 * Boolean settings accept only genuine booleans or checkbox values.
	 *
	 * @return void
	 */
	public function test_boolean_setting_is_cast_to_bool(): void {
		$setting = $this->make_setting( 'boolean' );

		$this->assertTrue( $setting->sanitize( '1' ) );
		$this->assertTrue( $setting->sanitize( true ) );
		$this->assertFalse( $setting->sanitize( '0' ) );
		$this->assertFalse( $setting->sanitize( '' ) );
	}

	/**
	 * Integer settings cast to int and reject junk.
	 *
	 * @return void
	 */
	public function test_integer_setting_is_cast_to_int(): void {
		$setting = $this->make_setting( 'integer' );

		$this->assertSame( 42, $setting->sanitize( '42' ) );
		$this->assertSame( 0, $setting->sanitize( 'not-a-number' ) );
	}

	/**
	 * Number settings accept numeric values, including decimals.
	 *
	 * @return void
	 */
	public function test_number_setting_is_cast_to_float(): void {
		$setting = $this->make_setting( 'number' );

		$this->assertSame( 1.5, $setting->sanitize( '1.5' ) );
		$this->assertSame( 0.0, $setting->sanitize( 'nonsense' ) );
	}

	/**
	 * String settings are sanitised as text.
	 *
	 * @return void
	 */
	public function test_string_setting_is_sanitised_as_text(): void {
		$setting = $this->make_setting( 'string' );

		$this->assertSame( 'hello', $setting->sanitize( '  hello  ' ) );
	}

	/**
	 * String sanitisation must strip tags so stored values cannot carry markup.
	 *
	 * @return void
	 */
	public function test_string_setting_strips_markup(): void {
		$setting = $this->make_setting( 'string' );

		$result = (string) $setting->sanitize( '<script>alert(1)</script>Hi' );

		$this->assertStringNotContainsString( '<script', $result );
		$this->assertStringContainsString( 'Hi', $result );
	}

	/**
	 * An unknown type must not be passed through unmodified.
	 *
	 * @return void
	 */
	public function test_unknown_type_falls_back_to_string_sanitisation(): void {
		$setting = $this->make_setting( 'mystery' );

		$result = (string) $setting->sanitize( '<b>x</b>' );

		$this->assertStringNotContainsString( '<b>', $result );
	}

	/**
	 * Builds a real Setting of the given type.
	 *
	 * Constructor order (15 args):
	 * group_id, options_group_slug, section_slug, key, name, description,
	 * default_value, type, input_type, input_options, input_max, input_min,
	 * enabled_by, overridden_by, settings.
	 *
	 * @param string $type       Declared type.
	 * @param string $input_type Input field type.
	 * @return Setting
	 */
	private function make_setting( string $type, string $input_type = '' ): Setting {
		if ( '' === $input_type ) {
			$input_type = $this->input_type_for( $type );
		}

		return new Setting(
			'test-group',
			'test-options-group',
			'test-section',
			'test_key',
			'Test Key',
			null,
			null,
			$type,
			$input_type,
			null,
			null,
			null,
			null,
			null,
			$this->make_settings_double()
		);
	}

	/**
	 * Creates a Settings instance without running its constructor.
	 *
	 * `Settings` is only stored by `Setting`, never used by the code under test,
	 * so constructor side effects (option loading) are unnecessary.
	 *
	 * @return \footnotes\includes\Settings
	 */
	private function make_settings_double(): \footnotes\includes\Settings {
		require_once dirname( __DIR__, 2 ) . '/includes/class-settings.php';

		$reflection = new \ReflectionClass( \footnotes\includes\Settings::class );

		return $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Maps a declared type to the input type that would render it.
	 *
	 * @param string $type Declared type.
	 * @return string Input type.
	 */
	private function input_type_for( string $type ): string {
		return match ( $type ) {
			'boolean' => 'checkbox',
			'integer', 'number' => 'number',
			default   => 'text',
		};
	}
}
