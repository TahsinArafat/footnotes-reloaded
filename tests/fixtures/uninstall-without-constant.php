<?php
/**
 * Fixture: include uninstall.php without defining WP_UNINSTALL_PLUGIN.
 *
 * Runs in its own process because WP_UNINSTALL_PLUGIN is a constant: once
 * defined anywhere in a process it cannot be undefined, so the guard cannot be
 * tested in-process.
 *
 * A correct uninstall.php calls exit() with the default status, 0. This fixture
 * distinguishes the three possible outcomes by exit code:
 *
 *   0   uninstall.php guarded correctly and exited
 *   42  execution reached the end of uninstall.php (the guard failed)
 *   1+  the file crashed (uncaught error)
 *
 * @package footnotes
 */

declare(strict_types=1);

// Minimal stand-in so uninstall.php can be included without WordPress.
if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Reports any attempted deletion.
	 *
	 * @param string $option Option name.
	 * @return bool Always true.
	 */
	function delete_option( $option ) {
		fwrite( STDERR, 'DELETED OPTION: ' . $option . "\n" );
		return true;
	}
}

require dirname( __DIR__, 2 ) . '/uninstall.php';

// Reached only if the guard failed.
exit( 42 );
