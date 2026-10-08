<?php
/**
 * Server render for cached-query-blocks/post-list.
 *
 * @package CachedQueryBlocks
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

use CachedQueryBlocks\Plugin;
use CachedQueryBlocks\Query\PostListQuery;

$cached_query_blocks_html = Plugin::instance()->renderer()->render(
	'cached-query-blocks/post-list',
	$attributes,
	array(),
	static fn() => PostListQuery::args( $attributes )
);

if ( '' === $cached_query_blocks_html ) {
	return;
}

printf(
	'<div %1$s>%2$s</div>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes.
	$cached_query_blocks_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped piece by piece in BlockRenderer before caching.
);
