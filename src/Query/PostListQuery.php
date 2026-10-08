<?php
/**
 * Query arguments for the post list block.
 *
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Query;

/**
 * Turns post list block attributes into sanitized WP_Query arguments.
 */
final class PostListQuery {

	/**
	 * @param array<string, mixed> $attributes Raw block attributes.
	 * @return array<string, mixed>|null Null when the attributes cannot produce a valid query.
	 */
	public static function args( array $attributes ): ?array {
		$post_type = sanitize_key( (string) ( $attributes['postType'] ?? 'post' ) );

		if ( ! CommonArgs::is_public_post_type( $post_type ) ) {
			return null;
		}

		$args = CommonArgs::build( $post_type, CommonArgs::posts_per_page( $attributes['postsPerPage'] ?? null ) );

		$taxonomy = sanitize_key( (string) ( $attributes['taxonomy'] ?? '' ) );
		$term_id  = absint( $attributes['termId'] ?? 0 );

		if ( '' === $taxonomy || 0 === $term_id ) {
			return $args;
		}

		// A filter that does not apply to this post type would silently widen the list, so refuse it.
		if ( ! is_object_in_taxonomy( $post_type, $taxonomy ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Single term filter; the result is cached.
		$args['tax_query'] = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_id,
			),
		);

		return $args;
	}
}
