<?php
/**
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Tests\Unit\Cache;

use Brain\Monkey\Functions;
use CachedQueryBlocks\Cache\Invalidator;
use CachedQueryBlocks\Cache\RenderCache;
use CachedQueryBlocks\Tests\Unit\TestCase;
use WP_Post;

final class InvalidatorTest extends TestCase {

	private const REVISION_ID = 10;

	private const AUTOSAVE_ID = 20;

	private RenderCache $cache;

	private Invalidator $invalidator;

	private string $salt_before;

	protected function setUp(): void {
		parent::setUp();

		$this->cache       = new RenderCache( true );
		$this->invalidator = new Invalidator( $this->cache );
		$this->salt_before = $this->cache->salt();

		Functions\when( 'wp_is_post_revision' )->alias( fn( WP_Post $post ) => self::REVISION_ID === $post->ID ? 1 : false );
		Functions\when( 'wp_is_post_autosave' )->alias( fn( WP_Post $post ) => self::AUTOSAVE_ID === $post->ID ? 1 : false );
		usleep( 1 );
	}

	private function assertInvalidated(): void {
		$this->assertNotSame( $this->salt_before, $this->cache->salt() );
	}

	private function assertNotInvalidated(): void {
		$this->assertSame( $this->salt_before, $this->cache->salt() );
	}

	public function test_saving_a_published_post_invalidates(): void {
		$this->invalidator->on_save_post( 1, new WP_Post( 1 ) );

		$this->assertInvalidated();
	}

	public function test_saving_a_draft_is_ignored(): void {
		$this->invalidator->on_save_post( 1, new WP_Post( 1, '', 'post', 'draft' ) );

		$this->assertNotInvalidated();
	}

	public function test_revisions_are_ignored(): void {
		$this->invalidator->on_save_post( self::REVISION_ID, new WP_Post( self::REVISION_ID ) );

		$this->assertNotInvalidated();
	}

	public function test_autosaves_are_ignored(): void {
		$this->invalidator->on_save_post( self::AUTOSAVE_ID, new WP_Post( self::AUTOSAVE_ID ) );

		$this->assertNotInvalidated();
	}

	public function test_non_viewable_post_types_are_ignored(): void {
		$this->invalidator->on_save_post( 1, new WP_Post( 1, '', 'nav_menu_item' ) );

		$this->assertNotInvalidated();
	}

	public function test_deleting_a_published_post_invalidates(): void {
		$this->invalidator->on_deleted_post( 1, new WP_Post( 1 ) );

		$this->assertInvalidated();
	}

	public function test_unpublishing_invalidates(): void {
		$this->invalidator->on_transition_post_status( 'draft', 'publish', new WP_Post( 1, '', 'post', 'draft' ) );

		$this->assertInvalidated();
	}

	public function test_transition_between_unpublished_states_is_ignored(): void {
		$this->invalidator->on_transition_post_status( 'pending', 'draft', new WP_Post( 1, '', 'post', 'pending' ) );

		$this->assertNotInvalidated();
	}

	public function test_term_assignment_on_a_published_post_invalidates(): void {
		Functions\when( 'get_post' )->justReturn( new WP_Post( 1 ) );

		$this->invalidator->on_set_object_terms( 1 );

		$this->assertInvalidated();
	}

	public function test_register_hooks_content_and_url_changes(): void {
		$this->invalidator->register();

		$this->assertNotFalse( has_action( 'save_post', array( $this->invalidator, 'on_save_post' ) ) );
		$this->assertNotFalse( has_action( 'deleted_post', array( $this->invalidator, 'on_deleted_post' ) ) );
		$this->assertNotFalse( has_action( 'transition_post_status', array( $this->invalidator, 'on_transition_post_status' ) ) );
		$this->assertNotFalse( has_action( 'set_object_terms', array( $this->invalidator, 'on_set_object_terms' ) ) );
		$this->assertNotFalse( has_action( 'edited_term', array( $this->invalidator, 'invalidate' ) ) );
		$this->assertNotFalse( has_action( 'update_option_permalink_structure', array( $this->invalidator, 'invalidate' ) ) );
		$this->assertNotFalse( has_action( 'update_option_home', array( $this->invalidator, 'invalidate' ) ) );
	}
}
