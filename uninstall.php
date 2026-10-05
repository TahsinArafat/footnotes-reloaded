<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes all data the plugin created. Deactivation is handled separately by
 * {@see includes\Deactivator}; this file only runs when a user deletes the
 * plugin, and WordPress defines WP_UNINSTALL_PLUGIN before including it.
 *
 * @package  footnotes
 * @since  2.8.0
 * @since  2.8.0 Delete the plugin's option groups and widget option.
 */

declare(strict_types=1);

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Option groups the plugin stores, keyed by the options group slug used in
 * {@see includes\Settings::$options_group_slugs}.
 *
 * @var string[]
 */
$footnotes_option_groups = array(
	'footnotes_storage',
	'footnotes_storage_custom',
	'footnotes_storage_expert',
	'footnotes_storage_custom_css',
);

foreach ( $footnotes_option_groups as $footnotes_option_group ) {
	delete_option( $footnotes_option_group );
}

// The reference container widget, stored under WordPress's widget_<id_base> key.
delete_option( 'widget_footnotes_widget' );
