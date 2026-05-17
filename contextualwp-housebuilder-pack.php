<?php
/**
 * Plugin Name:       ContextualWP Housebuilder Pack
 * Plugin URI:        https://github.com/kwd-it/contextualwp-housebuilder-pack
 * Description:       Housebuilder sector pack for ContextualWP. Relationship hints, schema interpretation, ACF semantic tagging, and a read-only authenticated plots REST feed; public reference for sector packs.
 * Version:           0.4.5
 * Requires at least: 6.4
 * Tested up to:      6.8
 * Requires PHP:      8.1
 * Author:            Kirk Johnston / KWD-IT
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       contextualwp-housebuilder-pack
 *
 * @package ContextualWPHousebuilderPack
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

const CONTEXTUALWP_HOUSEBUILDER_PACK_VERSION = '0.4.5';
const CONTEXTUALWP_HOUSEBUILDER_PACK_MIN_CONTEXTUALWP_VERSION = '1.3.3';

/**
 * Absolute plugin path to the main file.
 */
const CONTEXTUALWP_HOUSEBUILDER_PACK_FILE = __FILE__;

$autoload = __DIR__ . '/vendor/autoload.php';

if (is_readable($autoload)) {
	require_once $autoload;
}

// Built ZIP installs ship plugin-local vendor; Composer-managed sites may load
// this package only via the project root autoloader (no plugin/vendor directory).
if (!class_exists(\ContextualWP\HousebuilderPack\Bootstrap::class, true)) {
	add_action('admin_notices', static function (): void {
		if (!current_user_can('activate_plugins')) {
			return;
		}

		echo '<div class="notice notice-warning"><p>'
			. esc_html__('ContextualWP Housebuilder Pack could not load. Install it via Composer at project level, or use a built release package that includes vendor dependencies.', 'contextualwp-housebuilder-pack')
			. '</p></div>';
	});

	return;
}

add_action('plugins_loaded', static function (): void {
	$bootstrap = new \ContextualWP\HousebuilderPack\Bootstrap();
	$bootstrap->init();
}, 20);
