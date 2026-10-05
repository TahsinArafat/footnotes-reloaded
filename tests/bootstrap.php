<?php
/**
 * PHPUnit bootstrap for footnotes.
 *
 * Provides a minimal WordPress function/constant shim so that plugin classes
 * whose logic is pure (parsing, settings resolution, guard behaviour) can be
 * unit-tested without booting WordPress.
 *
 * Anything that genuinely needs WordPress (hooks firing, options storage) is
 * covered by integration tests, not here.
 *
 * @package footnotes
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// --- WordPress constants used at include time -----------------------------

defined( 'WPINC' ) || define( 'WPINC', 'wp-includes' );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
defined( 'PLUGIN_VERSION' ) || define( 'PLUGIN_VERSION', '2.8.0' );
defined( 'PRODUCTION_ENV' ) || define( 'PRODUCTION_ENV', false );
defined( 'PHP_INT_MAX' ) || define( 'PHP_INT_MAX', 9223372036854775807 );

// --- Minimal WordPress function shims -------------------------------------
//
// Only the functions needed by the code paths under test. Each records its
// calls so tests can assert on interactions.

if ( ! function_exists( 'plugin_dir_path' ) ) {
	/**
	 * Stub of plugin_dir_path().
	 *
	 * @param string $file Path to resolve against.
	 * @return string Trailing-slashed directory.
	 */
	function plugin_dir_path( string $file ): string {
		return rtrim( dirname( $file ), '/\\' ) . '/';
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	/**
	 * Stub of plugin_basename().
	 *
	 * @param string $file Path to reduce.
	 * @return string Base name.
	 */
	function plugin_basename( string $file ): string {
		return basename( $file );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Stub of add_action(): records registered handlers.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $args     Accepted args.
	 * @return void
	 */
	function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
		$GLOBALS['__wp_hooks'][ $hook ][] = array( $callback, $priority );
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Stub of add_filter(): records registered filters.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $args     Accepted args.
	 * @return void
	 */
	function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
		$GLOBALS['__wp_filters'][ $hook ][] = array( $callback, $priority );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Stub of apply_filters(): returns the value, applying registered filters.
	 *
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value to filter.
	 * @return mixed Filtered value.
	 */
	function apply_filters( $hook, $value ) {
		foreach ( $GLOBALS['__wp_filters'][ $hook ] ?? array() as $entry ) {
			$value = call_user_func( $entry[0], $value );
		}
		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Stub of do_action(): invokes registered handlers.
	 *
	 * @param string $hook Hook name.
	 * @return void
	 */
	function do_action( $hook ) {
		foreach ( $GLOBALS['__wp_hooks'][ $hook ] ?? array() as $entry ) {
			call_user_func( $entry[0] );
		}
	}
}

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
	/**
	 * Stub of load_plugin_textdomain(): records the call.
	 *
	 * @param string $domain          Text domain.
	 * @param bool   $deprecated      Deprecated argument.
	 * @param string $plugin_rel_path Relative path.
	 * @return bool Always true.
	 */
	function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
		$GLOBALS['__loaded_textdomains'][] = array(
			'domain' => $domain,
			'path'   => $plugin_rel_path,
		);
		return true;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Stub of __(): returns the input unchanged, records the call.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string Untranslated text.
	 */
	function __( $text, $domain = 'default' ) {
		$GLOBALS['__translation_calls'][] = array(
			'text'   => $text,
			'domain' => $domain,
		);
		return $text;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Stub of get_option(): reads from an in-memory option store.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default when absent.
	 * @return mixed Option value or default.
	 */
	function get_option( $option, $default = false ) {
		return $GLOBALS['__wp_options'][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Stub of update_option(): writes to the in-memory option store.
	 *
	 * @param string $option   Option name.
	 * @param mixed  $value    New value.
	 * @param mixed  $autoload Autoload flag (unused).
	 * @return bool Always true.
	 */
	function update_option( $option, $value, $autoload = null ) {
		$GLOBALS['__wp_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Stub of esc_attr().
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Stub of esc_html().
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Stub of esc_url().
	 *
	 * @param string $url URL to escape.
	 * @return string Escaped URL.
	 */
	function esc_url( $url ) {
		return (string) $url;
	}
}

if ( ! function_exists( 'sanitize_hex_color' ) ) {
	/**
	 * Stub of sanitize_hex_color().
	 *
	 * @param string $color Candidate colour.
	 * @return string|null Valid `#rgb`/`#rrggbb`, else null.
	 */
	function sanitize_hex_color( $color ) {
		$color = (string) $color;

		if ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
			return $color;
		}

		return null;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Stub of sanitize_text_field().
	 *
	 * @param string $text Text to sanitize.
	 * @return string Sanitised text.
	 */
	function sanitize_text_field( $text ) {
		return trim( strip_tags( (string) $text ) );
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Stub of delete_option(): removes from the store and records the call.
	 *
	 * @param string $option Option name.
	 * @return bool True when an option was removed, false otherwise.
	 */
	function delete_option( $option ) {
		$GLOBALS['__deleted_options'][] = $option;

		if ( array_key_exists( $option, $GLOBALS['__wp_options'] ) ) {
			unset( $GLOBALS['__wp_options'][ $option ] );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'register_setting' ) ) {
	/**
	 * Stub of register_setting().
	 *
	 * @param string $group  Settings group.
	 * @param string $name   Setting name.
	 * @param array  $args   Registration args.
	 * @return void
	 */
	function register_setting( $group, $name, $args = array() ) {
		$GLOBALS['__registered_settings'][] = array( $group, $name );
	}
}

if ( ! function_exists( 'add_settings_section' ) ) {
	/**
	 * Stub of add_settings_section().
	 *
	 * @param string   $id       Section id.
	 * @param string   $title    Section title.
	 * @param callable $callback Callback.
	 * @param string   $page     Page slug.
	 * @return void
	 */
	function add_settings_section( $id, $title, $callback, $page = '' ) {
		$GLOBALS['__settings_sections'][] = $id;
	}
}

if ( ! function_exists( 'add_settings_field' ) ) {
	/**
	 * Stub of add_settings_field().
	 *
	 * @param string   $id       Field id.
	 * @param string   $title    Field title.
	 * @param callable $callback Callback.
	 * @param string   $page     Page slug.
	 * @param string   $section  Section id.
	 * @param array    $args     Extra args.
	 * @return void
	 */
	function add_settings_field( $id, $title, $callback, $page = '', $section = '', $args = array() ) {
		$GLOBALS['__settings_fields'][] = $id;
	}
}

if ( ! function_exists( 'register_widget' ) ) {
	/**
	 * Stub of register_widget(): records the widget class name.
	 *
	 * @param string $widget Widget class name.
	 * @return void
	 */
	function register_widget( $widget ) {
		$GLOBALS['__registered_widgets'][] = $widget;
	}
}

if ( ! class_exists( 'WP_Widget' ) ) {
	/**
	 * Minimal stub of WP_Widget.
	 *
	 * Records constructor arguments so tests can assert on registration
	 * without loading WordPress.
	 */
	class WP_Widget {

		/**
		 * Arguments passed to the parent constructor.
		 *
		 * @var array
		 */
		public array $constructed_with = array();

		/**
		 * Constructor stub.
		 *
		 * @param string $id_base         Base ID for the widget.
		 * @param string $name            Widget display name.
		 * @param array  $widget_options  Optional widget options.
		 * @param array  $control_options Optional control options.
		 */
		public function __construct( $id_base = '', $name = '', $widget_options = array(), $control_options = array() ) {
			$this->constructed_with = array(
				'id_base'         => $id_base,
				'name'            => $name,
				'widget_options'  => $widget_options,
				'control_options' => $control_options,
			);
		}
	}
}

/**
 * Test helper: reset all recorded WordPress state between tests.
 *
 * @return void
 */
function footnotes_test_reset_wp_state(): void {
	$GLOBALS['__wp_hooks']            = array();
	$GLOBALS['__wp_filters']          = array();
	$GLOBALS['__loaded_textdomains']  = array();
	$GLOBALS['__translation_calls']   = array();
	$GLOBALS['__wp_options']          = array();
	$GLOBALS['__deleted_options']     = array();
	$GLOBALS['__registered_settings'] = array();
	$GLOBALS['__settings_sections']   = array();
	$GLOBALS['__settings_fields']     = array();
	$GLOBALS['__registered_widgets']  = array();
}
