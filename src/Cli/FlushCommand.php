<?php
/**
 * WP-CLI command.
 *
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Cli;

use CachedQueryBlocks\Cache\RenderCache;

/**
 * Manages the Cached Query Blocks render cache.
 */
final class FlushCommand {

	/**
	 * @param RenderCache $cache Render cache.
	 */
	public function __construct( private RenderCache $cache ) {}

	/**
	 * Invalidates every cached block render by bumping the cache salt.
	 *
	 * Old entries are not deleted; they are no longer read and expire on their TTL.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cqb flush
	 */
	public function flush(): void {
		$this->cache->bump_salt();
		\WP_CLI::success( 'Cached Query Blocks render cache invalidated.' );
	}
}
