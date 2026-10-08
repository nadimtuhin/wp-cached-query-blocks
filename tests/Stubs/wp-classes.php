<?php
/**
 * Minimal stand-ins for the WordPress classes the plugin type-hints against.
 *
 * @package CachedQueryBlocks
 */

// phpcs:disable

class WP_Post {
	public int $ID = 0;

	public string $post_password = '';

	public string $post_type = 'post';

	public string $post_status = 'publish';

	public function __construct( int $id = 0, string $post_password = '', string $post_type = 'post', string $post_status = 'publish' ) {
		$this->ID            = $id;
		$this->post_password = $post_password;
		$this->post_type     = $post_type;
		$this->post_status   = $post_status;
	}
}

class WP_Query {
	/** @var array<int, WP_Post> */
	public array $posts = array();
}
