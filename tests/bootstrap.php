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
defined( 'PLUGIN_VERSION' ) || define( 'PLUGIN_VERSION', '2.8.0p' );
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
			$value = $entry[0]( $value );
		}
		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Stub of do_action().
	 *
	 * @param string $hook Hook name.
	 * @return void
	 */
	function do_action( $hook ) {
		foreach ( $GLOBALS['__wp_hooks'][ $hook ] ?? array() as $entry ) {
			$entry[0]();
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
}
