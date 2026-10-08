<?php
/**
 * Server render for cached-query-blocks/related-posts.
 *
 * @package CachedQueryBlocks
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var WP_Block             $block      Block instance.
 */

use CachedQueryBlocks\Plugin;
use CachedQueryBlocks\Query\RelatedPostsQuery;

// The editor preview has no block context, but the block-renderer endpoint sets up the global post.
$cached_query_blocks_post_id = (int) ( $block->context['postId'] ?? get_the_ID() );

$cached_query_blocks_html = Plugin::instance()->renderer()->render(
	'cached-query-blocks/related-posts',
	$attributes,
	array( 'postId' => $cached_query_blocks_post_id ),
	static fn() => RelatedPostsQuery::args( $cached_query_blocks_post_id, $attributes )
);

if ( '' === $cached_query_blocks_html ) {
	return;
}

printf(
	'<div %1$s>%2$s</div>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes.
	$cached_query_blocks_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped piece by piece in BlockRenderer before caching.
);
