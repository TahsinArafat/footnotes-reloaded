=== footnotes-reloaded ===
Contributors: mark.cheret, lolzim, rumperuu, aricura, misfist, ericakfranz, milindmore22, westonruter, dartiss, derivationfr, docteurfitness, felipelavinz, martinneumannat, matkus2, meglio, spaceling, vonpiernik, pewgeuges
Tags: footnote, footnotes, bibliography, formatting, notes, Post, posts, reference, referencing
Requires at least: 5.4
Tested up to: 6.7
Requires PHP: 8.0
Stable Tag: 2.8.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

footnotes lets you easily add highly-customisable footnotes on your WordPress Pages and Posts.

== Description ==

**footnotes-reloaded** aims to be the all-in-one solution for displaying an automatically-generated list of references on your Page or Post. The Plugin ships with a set of defaults while also empowering you to control how your footnotes are being displayed.

**footnotes-reloaded** gives you the ability to display well-formatted footnotes on your WordPress Pages and Posts, as well as in post excerpts with fully functional tooltips if enabled.

This is a maintained continuation of the **footnotes** plugin originally written by
[Mark Cheret](https://cheret.tech/footnotes) and contributors. The original plugin was
[closed on WordPress.org in November 2022](https://wordpress.org/plugins/footnotes/) at
the author's request and has not been updated since. This fork carries the plugin
forward, with particular attention to compatibility with current WordPress and PHP
versions.

= Main Features =

- Fully-customizable **footnote** start and end shortcodes;
- styled hyperlink tooltips;
- responsive reference container with customisable position;
- ability to display reference container inside a widget;
- wide choice of numbering styles;
- freely-configurable optional backlink symbol;
- configurable footnote appearance; and
- shortcodes button in Post editor.

== Frequently Asked Questions ==

= Is this the original plugin? =

No. This is a maintained fork of the **footnotes** plugin, which was closed on
WordPress.org in November 2022 and is no longer maintained. All credit for the original
plugin belongs to Mark Cheret and the contributors listed above. This fork continues
development under the GPLv3, as the licence requires and permits.

= How do I convert my footnotes if I used another footnotes plugin? =

Most footnotes plugins use open and close shortcodes, which can be left as-is. In the
**footnotes** settings, you can configure the plugin to use your existing shortcodes.

= What are the system requirements? =

WordPress 5.4 or later, and PHP 8.0 or later.

== Screenshots ==

1. Plugin settings can be found under the default 'Settings' menu.
2. Settings for the *References Container*.
3. Settings for footnotes styling.
4. Settings for **footnotes** love.
5. Other Settings.
6. The How-To section in the **footnotes** settings.
7. Here you can see the **footnotes** Plugin at work.

== Changelog ==

= 2.8.0 =

Fork maintenance release. Compatibility and correctness fixes on top of 2.7.3.

- Fix: Internationalization: load the textdomain on `init` rather than
  `plugins_loaded`. WordPress 6.7 and later emit
  `_load_textdomain_just_in_time was called incorrectly` otherwise, which broke
  admin page rendering.
- Fix: Internationalization: construct the reference container widget lazily on
  `widgets_init`. Its constructor resolved a translated string, which triggered
  the same WordPress 6.7 notice.
- Fix: Editor: guard the Classic Editor `QTags` registration with a `typeof`
  check. The previous bare identifier check threw
  `Uncaught ReferenceError: QTags is not defined` when Quicktags was absent.
- Docs: correct the readme metadata (supported WordPress and PHP versions were
  stale) and record the fork's provenance.

= 2.7.3 =

- Bugfix: fix WYSIWYG editor error message, thanks to @ogbcashdown bug report.

= 2.7.2 =

- Reissue of 2.7.1.

= 2.7.1 =

- Bugfix: Stylesheets: namespace collapsed CSS class, thanks to @cybermrmotte @markyz89 bug reports.
- Dashboard: move Plugin settings under default WP Settings menu.
- Bugfix: Footnotes: fix bug when using multiple paragraphs in footnotes.
- Documentation: remove outdated MCI/ManFisher references.
- Documentation: split changelog into seperate file.

= 2.7.0 =

- Adding: Reference container: optionally per section by shortcode, thanks to @grflukas issue report.
- Bugfix: Excerpts: make excerpt handling backward compatible, thanks to @mfessler bug report.
- Bugfix: Dashboard: debug the 'Quick start guide' tab, thanks to @rumperuu bug report.

== Upgrade Notice ==

= 2.8.0 =

Adds compatibility with WordPress 6.7 and later, and requires PHP 8.0 or later.

== Usage ==

These are a few examples of possible ways to delimit your footnotes:

1. Your awesome text((with an awesome footnote))
2. Your awesome text[ref]with an awesome footnote[/ref]
3. Your awesome text&lt;fn&rt;with an awesome footnote&lt;/fn&gt;
4. Your awesome text`custom-start-shortcode`with an awesome footnote`custom-end-shortcode`

== Support ==

Please report bugs and feature requests on the
[GitHub issue tracker](https://github.com/TahsinArafat/footnotes-reloaded/issues).
