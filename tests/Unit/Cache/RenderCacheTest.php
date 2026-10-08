<?php
/**
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Tests\Unit\Cache;

use CachedQueryBlocks\Cache\RenderCache;
use CachedQueryBlocks\Tests\Unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class RenderCacheTest extends TestCase {

	private const BLOCK = 'cached-query-blocks/post-list';

	public function test_key_changes_when_an_attribute_changes(): void {
		$cache = new RenderCache( true );

		$five = $cache->key( self::BLOCK, array( 'postsPerPage' => 5 ) );
		$six  = $cache->key( self::BLOCK, array( 'postsPerPage' => 6 ) );

		$this->assertNotSame( $five, $six );
	}

	public function test_key_is_stable_when_attribute_order_differs(): void {
		$cache = new RenderCache( true );

		$first  = $cache->key( self::BLOCK, array( 'postType' => 'post', 'postsPerPage' => 5, 'style' => array( 'a' => 1, 'b' => 2 ) ) );
		$second = $cache->key( self::BLOCK, array( 'style' => array( 'b' => 2, 'a' => 1 ), 'postsPerPage' => 5, 'postType' => 'post' ) );

		$this->assertSame( $first, $second );
	}

	public function test_key_changes_with_context(): void {
		$cache = new RenderCache( true );

		$this->assertNotSame(
			$cache->key( 'cached-query-blocks/related-posts', array(), array( 'postId' => 1 ) ),
			$cache->key( 'cached-query-blocks/related-posts', array(), array( 'postId' => 2 ) )
		);
	}

	public function test_bumping_the_salt_changes_the_key(): void {
		$cache  = new RenderCache( true );
		$before = $cache->key( self::BLOCK, array() );

		usleep( 1 );
		$cache->bump_salt();

		$this->assertNotSame( $before, $cache->key( self::BLOCK, array() ) );
	}

	public function test_salt_survives_between_instances_without_object_cache(): void {
		$key = ( new RenderCache( false ) )->key( self::BLOCK, array() );

		$this->assertSame( $key, ( new RenderCache( false ) )->key( self::BLOCK, array() ) );
		$this->assertArrayHasKey( 'option:cqb_last_changed', $this->store );
	}

	#[DataProvider( 'ttl_cases' )]
	public function test_ttl_is_clamped( int $requested, int $expected ): void {
		$this->assertSame( $expected, RenderCache::clamp_ttl( $requested ) );
	}

	/**
	 * @return array<string, array{int, int}>
	 */
	public static function ttl_cases(): array {
		return array(
			'below minimum' => array( 0, 60 ),
			'negative'      => array( -100, 60 ),
			'in range'      => array( 600, 600 ),
			'above maximum' => array( 999999, 86400 ),
		);
	}

	public function test_set_uses_object_cache_with_clamped_ttl_when_persistent(): void {
		$cache = new RenderCache( true );

		$cache->set( 'k', '<ul></ul>', 5 );

		$this->assertSame( '<ul></ul>', $cache->get( 'k' ) );
		$this->assertSame( 60, $this->ttls['cqb:k'] );
	}

	public function test_set_falls_back_to_transients_without_object_cache(): void {
		$cache = new RenderCache( false );

		$cache->set( 'k', '<ul></ul>', 100000 );

		$this->assertSame( '<ul></ul>', $cache->get( 'k' ) );
		$this->assertSame( 86400, $this->ttls['transient:k'] );
	}

	public function test_get_returns_null_on_miss(): void {
		$this->assertNull( ( new RenderCache( true ) )->get( 'missing' ) );
	}
}
