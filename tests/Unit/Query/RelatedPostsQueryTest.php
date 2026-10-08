<?php
/**
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Tests\Unit\Query;

use Brain\Monkey\Functions;
use CachedQueryBlocks\Query\RelatedPostsQuery;
use CachedQueryBlocks\Tests\Unit\TestCase;

final class RelatedPostsQueryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'get_post_type' )->justReturn( 'post' );
		Functions\when( 'get_post_type_object' )->justReturn( (object) array( 'public' => true ) );
		Functions\when( 'get_object_taxonomies' )->justReturn(
			array(
				(object) array( 'name' => 'category', 'public' => true ),
				(object) array( 'name' => 'post_format', 'public' => true ),
				(object) array( 'name' => 'internal', 'public' => false ),
			)
		);
		Functions\when( 'wp_list_pluck' )->alias( fn( $list, $field ) => array_map( fn( $item ) => $item->$field, $list ) );
	}

	public function test_matches_any_shared_public_term_and_excludes_current_post(): void {
		Functions\when( 'get_the_terms' )->justReturn( array( (object) array( 'term_id' => 3 ), (object) array( 'term_id' => 4 ) ) );

		$args = RelatedPostsQuery::args( 42, array( 'postsPerPage' => 3 ) );

		$this->assertSame(
			array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => array( 3, 4 ),
				),
			),
			$args['tax_query']
		);
		$this->assertSame( array( 42 ), $args['post__not_in'] );
		$this->assertSame( 3, $args['posts_per_page'] );
		$this->assertTrue( $args['no_found_rows'] );
	}

	public function test_post_without_terms_has_nothing_to_relate_to(): void {
		Functions\when( 'get_the_terms' )->justReturn( false );

		$this->assertNull( RelatedPostsQuery::args( 42, array() ) );
	}

	public function test_missing_post_id_returns_null(): void {
		$this->assertNull( RelatedPostsQuery::args( 0, array() ) );
	}
}
