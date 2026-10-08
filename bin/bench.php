<?php
/**
 * Compares cold (cache miss) and warm (cache hit) renders of the post list block.
 *
 * Run inside a WordPress install that has this plugin active and some published posts:
 *
 *     wp eval-file wp-content/plugins/cached-query-blocks/bin/bench.php 200
 *
 * The optional argument is the number of renders per mode (default 100).
 *
 * @package CachedQueryBlocks
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( 1 );
}

$cached_query_blocks_iterations = max( 1, (int) ( $args[0] ?? 100 ) );

$cached_query_blocks_block = array(
	'blockName'    => 'cached-query-blocks/post-list',
	'attrs'        => array( 'postsPerPage' => 10, 'showExcerpt' => true ),
	'innerBlocks'  => array(),
	'innerHTML'    => '',
	'innerContent' => array(),
);

$cached_query_blocks_persistent = wp_using_ext_object_cache();
$cached_query_blocks_cache      = new CachedQueryBlocks\Cache\RenderCache( $cached_query_blocks_persistent );

$cached_query_blocks_measure = static function ( bool $cold ) use ( $cached_query_blocks_block, $cached_query_blocks_iterations, $cached_query_blocks_cache, $cached_query_blocks_persistent ): array {
	global $wpdb;

	$queries = 0;
	$seconds = 0.0;

	for ( $i = 0; $i < $cached_query_blocks_iterations; $i++ ) {
		if ( $cold ) {
			$cached_query_blocks_cache->bump_salt();
			// Without this, core's own WP_Query cache (WP 6.1+) would answer the "miss" after the first round.
			wp_cache_set_last_changed( 'posts' );
		}

		// Each real page view starts with an empty runtime cache; without a persistent backend, simulate that.
		if ( ! $cached_query_blocks_persistent ) {
			wp_cache_flush_runtime();
		}

		$before_queries = $wpdb->num_queries;
		$start          = hrtime( true );

		render_block( $cached_query_blocks_block );

		$seconds += ( hrtime( true ) - $start ) / 1e9;
		$queries += $wpdb->num_queries - $before_queries;
	}

	return array(
		'avg_ms'      => round( $seconds / $cached_query_blocks_iterations * 1000, 3 ),
		'avg_queries' => round( $queries / $cached_query_blocks_iterations, 2 ),
	);
};

render_block( $cached_query_blocks_block );

$cached_query_blocks_rows = array(
	array_merge( array( 'mode' => 'miss' ), $cached_query_blocks_measure( true ) ),
	array_merge( array( 'mode' => 'hit' ), $cached_query_blocks_measure( false ) ),
);

WP_CLI::line( sprintf( 'Object cache: %s. Renders per mode: %d.', $cached_query_blocks_persistent ?'persistent' : 'none (transients)', $cached_query_blocks_iterations ) );
WP_CLI\Utils\format_items( 'table', $cached_query_blocks_rows, array( 'mode', 'avg_ms', 'avg_queries' ) );
