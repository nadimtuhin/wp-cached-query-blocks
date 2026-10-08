<?php
/**
 * Base test case.
 *
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Sets up Brain Monkey and an in-memory stand-in for the object cache, transients and options.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase {

	use MockeryPHPUnitIntegration;

	/** @var array<string, mixed> */
	protected array $store = array();

	/** @var array<string, int> */
	protected array $ttls = array();

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->store = array();
		$this->ttls  = array();

		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'is_post_type_viewable' )->alias(
			fn( $type ) => is_object( $type ) ? ! empty( $type->public ) : in_array( $type, array( 'post', 'page' ), true )
		);

		$read  = fn( string $key ) => $this->store[ $key ] ?? false;
		$write = function ( string $key, $value, int $ttl = 0 ): bool {
			$this->store[ $key ] = $value;
			$this->ttls[ $key ]  = $ttl;
			return true;
		};

		Functions\when( 'wp_cache_get' )->alias( fn( $key, $group ) => $read( "$group:$key" ) );
		Functions\when( 'wp_cache_set' )->alias( fn( $key, $value, $group, $ttl = 0 ) => $write( "$group:$key", $value, $ttl ) );
		Functions\when( 'get_transient' )->alias( fn( $key ) => $read( "transient:$key" ) );
		Functions\when( 'set_transient' )->alias( fn( $key, $value, $ttl ) => $write( "transient:$key", $value, $ttl ) );
		Functions\when( 'get_option' )->alias( fn( $key ) => $read( "option:$key" ) );
		Functions\when( 'update_option' )->alias( fn( $key, $value ) => $write( "option:$key", $value ) );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
