<?php
/**
 * Plugin Name:       Cached Query Blocks
 * Description:       Post list and related posts blocks whose rendered HTML is cached and invalidated when content changes.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Nadim Tuhin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cached-query-blocks
 *
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks;

defined( 'ABSPATH' ) || exit;

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

Plugin::instance( __DIR__ )->boot();
