<?php
/**
 * Invalidates cached block HTML when content changes.
 *
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Cache;

use WP_Post;

/**
 * Bumps the render cache salt when something the blocks display changes.
 *
 * Every bump discards every cached block, so it only reacts to changes a
 * visitor could see: published posts of viewable types, terms, and URLs.
 */
final class Invalidator {

	private const URL_OPTIONS = array( 'permalink_structure', 'category_base', 'tag_base', 'home', 'siteurl' );

	/**
	 * @param RenderCache $cache Cache whose salt gets bumped.
	 */
	public function __construct( private RenderCache $cache ) {}

	public function register(): void {
		add_action( 'save_post', array( $this, 'on_save_post' ), 10, 2 );
		add_action( 'deleted_post', array( $this, 'on_deleted_post' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'on_transition_post_status' ), 10, 3 );
		add_action( 'set_object_terms', array( $this, 'on_set_object_terms' ) );
		add_action( 'edited_term', array( $this, 'invalidate' ) );

		foreach ( self::URL_OPTIONS as $option ) {
			add_action( "update_option_{$option}", array( $this, 'invalidate' ) );
		}
	}

	/**
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Saved post.
	 */
	public function on_save_post( $post_id, $post ): void {
		if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
			$this->invalidate_for( $post );
		}
	}

	/**
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Deleted post.
	 */
	public function on_deleted_post( $post_id, $post = null ): void {
		$this->on_save_post( $post_id, $post );
	}

	/**
	 * Covers publish, unpublish and trash, where save_post alone would miss the post leaving the list.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function on_transition_post_status( $new_status, $old_status, $post ): void {
		if ( $new_status === $old_status || ! in_array( 'publish', array( $new_status, $old_status ), true ) ) {
			return;
		}

		if ( $post instanceof WP_Post ) {
			$this->invalidate_for( $post );
		}
	}

	/**
	 * @param int $object_id Object the terms were assigned to.
	 */
	public function on_set_object_terms( $object_id ): void {
		$this->on_save_post( $object_id, get_post( (int) $object_id ) );
	}

	public function invalidate(): void {
		$this->cache->bump_salt();
	}

	private function invalidate_for( WP_Post $post ): void {
		if ( false !== wp_is_post_revision( $post ) || false !== wp_is_post_autosave( $post ) ) {
			return;
		}

		if ( ! is_post_type_viewable( $post->post_type ) ) {
			return;
		}

		$this->invalidate();
	}
}
