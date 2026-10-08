<?php
/**
 * WP_Query arguments shared by every block.
 *
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Query;

/**
 * Builds the lean WP_Query arguments both blocks start from.
 */
final class CommonArgs {

	public const MIN_POSTS = 1;

	public const MAX_POSTS = 20;

	public const DEFAULT_POSTS = 5;

	public static function posts_per_page( mixed $requested ): int {
		$count = is_numeric( $requested ) ? (int) $requested : self::DEFAULT_POSTS;

		return max( self::MIN_POSTS, min( self::MAX_POSTS, $count ) );
	}

	/**
	 * @param string $post_type      A post type already checked to be public.
	 * @param int    $posts_per_page Clamped post count.
	 * @return array<string, mixed>
	 */
	public static function build( string $post_type, int $posts_per_page ): array {
		return array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => $posts_per_page,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => self::permalinks_need_terms( $post_type ),
		);
	}

	public static function is_public_post_type( string $post_type ): bool {
		$object = '' === $post_type ? null : get_post_type_object( $post_type );

		return null !== $object && is_post_type_viewable( $object );
	}

	/**
	 * The markup only prints titles, links and excerpts. Terms are needed only when
	 * get_permalink() has to fill a %category% tag, otherwise it runs one query per post.
	 */
	private static function permalinks_need_terms( string $post_type ): bool {
		return 'post' === $post_type
			&& str_contains( (string) get_option( 'permalink_structure' ), '%category%' );
	}
}
