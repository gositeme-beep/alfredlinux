<?php
/**
 * Plugin Name: AIOSEO - IndexNow
 * Plugin URI:  https://aioseo.com
 * Description: Adds IndexNow support for AIOSEO.
 * Author:      All in One SEO Team
 * Author URI:  https://aioseo.com
 * Version:     1.0.6
 * Text Domain: aioseo-index-now
 * Domain Path: languages
 *
 * All in One SEO is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * All in One SEO is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with All in One SEO. If not, see <https://www.gnu.org/licenses/>.
 *
 * @since     1.0.0
 * @author    All in One SEO
 * @package   AIOSEO\Extend\Addon
 * @license   GPL-2.0+
 * @copyright Copyright (c) 2020, All in One SEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'AIOSEO_INDEX_NOW_FILE', __FILE__ );
define( 'AIOSEO_INDEX_NOW_DIR', __DIR__ );
define( 'AIOSEO_INDEX_NOW_PATH', plugin_dir_path( AIOSEO_INDEX_NOW_FILE ) );
define( 'AIOSEO_INDEX_NOW_URL', plugin_dir_url( AIOSEO_INDEX_NOW_FILE ) );

// Require our translation downloader.
require_once( __DIR__ . '/extend/translations.php' );

add_action( 'init', 'aioseo_index_now_translations' );
function aioseo_index_now_translations() {
	$translations = new AIOSEOTranslations(
		'plugin',
		'aioseo-index-now',
		'https://packages.translationspress.com/aioseo/aioseo-index-now/packages.json'
	);
	$translations->init();

	// @NOTE: The slugs here need to stay as aioseo-addon.
	$addonTranslations = new AIOSEOTranslations(
		'plugin',
		'aioseo-addon',
		'https://packages.translationspress.com/aioseo/aioseo-addon/packages.json'
	);
	$addonTranslations->init();
}

// Require our plugin compatibility checker.
require_once( __DIR__ . '/extend/init.php' );

// Check if this plugin should be disabled.
if ( aioseoAddonIsDisabled( 'aioseo-index-now' ) ) {
	return;
}

// Plugin compatibility checks.
new AIOSEOExtend( 'AIOSEO - IndexNow', 'aioseo_index_now_load', AIOSEO_INDEX_NOW_FILE, '4.2.7' );

/**
 * Function to load the addon.
 *
 * @since 1.0.0
 *
 * @return void
 */
function aioseo_index_now_load() {
	$levels = aioseo()->addons->getAddonLevels( 'aioseo-index-now' );
	$extend = new AIOSEOExtend( 'AIOSEO - IndexNow', '', AIOSEO_INDEX_NOW_FILE, '4.2.7', $levels );

	$addon = aioseo()->addons->getAddon( 'aioseo-index-now' );
	if ( ! $addon->hasMinimumVersion ) {
		return $extend->requiresUpdate();
	}

	if ( ! aioseo()->pro ) {
		return $extend->requiresPro();
	}

	// We don't want to return if the plan is only expired.
	if ( aioseo()->license->isExpired() ) {
		$extend->requiresUnexpiredLicense();
		$extend->disableNotices = true;
	}

	if ( aioseo()->license->isInvalid() || aioseo()->license->isDisabled() ) {
		return $extend->requiresActiveLicense();
	}

	if ( ! aioseo()->license->isAddonAllowed( 'aioseo-index-now' ) ) {
		return $extend->requiresPlanLevel();
	}

	require_once( __DIR__ . '/app/IndexNow.php' );

	aioseoIndexNow();
}