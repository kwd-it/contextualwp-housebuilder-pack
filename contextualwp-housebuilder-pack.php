<?php
/**
 * Plugin Name:       ContextualWP Housebuilder Pack
 * Plugin URI:        https://github.com/kwd-it/contextualwp-housebuilder-pack
 * Description:       Housebuilder sector pack for ContextualWP. Reference implementation scaffold for industry-specific packs.
 * Version:           0.1.0
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

const CONTEXTUALWP_HOUSEBUILDER_PACK_VERSION = '0.1.0';
const CONTEXTUALWP_HOUSEBUILDER_PACK_MIN_CONTEXTUALWP_VERSION = '1.1.0';

/**
 * Absolute plugin path to the main file.
 */
const CONTEXTUALWP_HOUSEBUILDER_PACK_FILE = __FILE__;

$autoload = __DIR__ . '/vendor/autoload.php';

if (is_readable($autoload)) {
	require_once $autoload;
} else {
	// Fail safely when Composer dependencies are not installed.
	add_action('admin_notices', static function (): void {
		if (!current_user_can('activate_plugins')) {
			return;
		}

		echo '<div class="notice notice-warning"><p>'
			. esc_html__('ContextualWP Housebuilder Pack is installed but its dependencies are missing. Run Composer install (vendor/autoload.php not found).', 'contextualwp-housebuilder-pack')
			. '</p></div>';
	});

	return;
}

if (!class_exists(\ContextualWP\HousebuilderPack\Bootstrap::class)) {
	// Defensive: autoloader present but expected class not found.
	add_action('admin_notices', static function (): void {
		if (!current_user_can('activate_plugins')) {
			return;
		}

		echo '<div class="notice notice-warning"><p>'
			. esc_html__('ContextualWP Housebuilder Pack is installed but failed to load. Expected Bootstrap class was not found. Reinstall dependencies and ensure the plugin files are complete.', 'contextualwp-housebuilder-pack')
			. '</p></div>';
	});

	return;
}

add_action('plugins_loaded', static function (): void {
	$bootstrap = new \ContextualWP\HousebuilderPack\Bootstrap();
	$bootstrap->init();
}, 20);
