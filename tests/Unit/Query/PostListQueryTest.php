<?php
/**
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Tests\Unit\Query;

use Brain\Monkey\Functions;
use CachedQueryBlocks\Query\PostListQuery;
use CachedQueryBlocks\Tests\Unit\TestCase;

final class PostListQueryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'sanitize_key' )->alias( fn( $key ) => preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) );
		Functions\when( 'absint' )->alias( fn( $value ) => abs( (int) $value ) );
		Functions\when( 'get_post_type_object' )->alias(
			fn( $type ) => match ( $type ) {
				'post'          => (object) array( 'public' => true ),
				'private_thing' => (object) array( 'public' => false ),
				default         => null,
			}
		);
		Functions\when( 'is_object_in_taxonomy' )->alias( fn( $type, $taxonomy ) => 'category' === $taxonomy );
	}

	public function test_non_public_post_type_is_rejected(): void {
		$this->assertNull( PostListQuery::args( array( 'postType' => 'private_thing' ) ) );
	}

	public function test_unregistered_post_type_is_rejected(): void {
		$this->assertNull( PostListQuery::args( array( 'postType' => 'nope' ) ) );
	}

	public function test_posts_per_page_is_clamped(): void {
		$this->assertSame( 20, PostListQuery::args( array( 'postsPerPage' => 500 ) )['posts_per_page'] );
		$this->assertSame( 1, PostListQuery::args( array( 'postsPerPage' => -3 ) )['posts_per_page'] );
		$this->assertSame( 5, PostListQuery::args( array( 'postsPerPage' => 'abc' ) )['posts_per_page'] );
	}

	public function test_query_skips_work_the_markup_does_not_need(): void {
		$args = PostListQuery::args( array() );

		$this->assertSame( 'post', $args['post_type'] );
		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertTrue( $args['no_found_rows'] );
		$this->assertTrue( $args['ignore_sticky_posts'] );
		$this->assertFalse( $args['update_post_meta_cache'] );
		$this->assertFalse( $args['update_post_term_cache'] );
	}

	public function test_term_cache_is_primed_when_permalinks_use_category(): void {
		$this->store['option:permalink_structure'] = '/%category%/%postname%/';

		$this->assertTrue( PostListQuery::args( array() )['update_post_term_cache'] );
	}

	public function test_term_filter_is_added_with_a_sanitized_term_id(): void {
		$args = PostListQuery::args( array( 'taxonomy' => 'category', 'termId' => '7abc' ) );

		$this->assertSame(
			array(
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => 7,
				),
			),
			$args['tax_query']
		);
	}

	public function test_taxonomy_not_attached_to_post_type_is_rejected(): void {
		$this->assertNull( PostListQuery::args( array( 'taxonomy' => 'genre', 'termId' => 3 ) ) );
	}
}
