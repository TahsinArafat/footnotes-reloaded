<?php // phpcs:disable PEAR.NamingConventions.ValidClassName.StartWithCapital
/**
 * File providing core `i18n` class.
 *
 * @package footnotes
 * @since 1.5.0
 * @since 2.8.0 Rename file from `language.php` to `class-footnotes-i18n.php`,
 *                              rename `class/` sub-directory to `includes/`.
 */

declare(strict_types=1);

namespace footnotes\includes;

require_once plugin_dir_path( __DIR__ ) . 'includes/class-config.php';

/**
 * Class providing internationalization functionality.
 *
 * Since WordPress 4.6, translations for a plugin hosted on WordPress.org are
 * loaded automatically from translate.wordpress.org; calling
 * {@see load_plugin_textdomain()} is neither necessary nor permitted by the
 * plugin review guidelines. This class therefore only declares the text domain
 * the plugin uses.
 *
 * @link https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/
 *
 * @package footnotes
 * @since 1.5.0
 * @since 2.8.0 Rename class from `Language` to `i18n`.
 * @since 2.8.0 Drop the textdomain loader; WordPress loads translations itself.
 */
class i18n {

	/**
	 * The plugin's text domain.
	 *
	 * Must match the plugin slug for translations to be served by WordPress.org.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public const TEXT_DOMAIN = 'footnotes';

}
