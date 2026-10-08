<?php
/**
 * Query arguments for the related posts block.
 *
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Query;

/**
 * Finds posts that share at least one public term with the current post.
 */
final class RelatedPostsQuery {

	/**
	 * @param int                  $post_id    The post being viewed.
	 * @param array<string, mixed> $attributes Raw block attributes.
	 * @return array<string, mixed>|null Null when there is nothing to relate to.
	 */
	public static function args( int $post_id, array $attributes ): ?array {
		$post_type = $post_id > 0 ? (string) get_post_type( $post_id ) : '';

		if ( ! CommonArgs::is_public_post_type( $post_type ) ) {
			return null;
		}

		$tax_query = self::shared_terms_clause( $post_id, $post_type );

		if ( null === $tax_query ) {
			return null;
		}

		$args = CommonArgs::build( $post_type, CommonArgs::posts_per_page( $attributes['postsPerPage'] ?? null ) );

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- The result is cached per post.
		$args['tax_query']    = $tax_query;
		$args['post__not_in'] = array( $post_id );

		return $args;
	}

	/**
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Post type of $post_id.
	 * @return array<int|string, mixed>|null
	 */
	private static function shared_terms_clause( int $post_id, string $post_type ): ?array {
		$clauses = array();

		foreach ( get_object_taxonomies( $post_type, 'objects' ) as $taxonomy ) {
			if ( ! $taxonomy->public || 'post_format' === $taxonomy->name ) {
				continue;
			}

			$terms = get_the_terms( $post_id, $taxonomy->name );

			if ( ! is_array( $terms ) || array() === $terms ) {
				continue;
			}

			$clauses[] = array(
				'taxonomy' => $taxonomy->name,
				'field'    => 'term_id',
				'terms'    => wp_list_pluck( $terms, 'term_id' ),
			);
		}

		if ( array() === $clauses ) {
			return null;
		}

		return array_merge( array( 'relation' => 'OR' ), $clauses );
	}
}
