<?php
/**
 * PHPUnit bootstrap. Runs without WordPress; WordPress functions are stubbed per test.
 *
 * @package CachedQueryBlocks
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once __DIR__ . '/Stubs/wp-classes.php';

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
