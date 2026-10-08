<?php
/**
 * Plugin bootstrap.
 *
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks;

use CachedQueryBlocks\Cache\Invalidator;
use CachedQueryBlocks\Cache\RenderCache;
use CachedQueryBlocks\Cli\FlushCommand;
use CachedQueryBlocks\Render\BlockRenderer;
use WP_Query;

/**
 * Wires the cache, invalidation hooks, blocks and CLI command together.
 */
final class Plugin {

	private const BLOCKS = array( 'post-list', 'related-posts' );

	private static ?Plugin $instance = null;

	private RenderCache $cache;

	private ?BlockRenderer $renderer = null;

	/**
	 * The block render.php templates need a way back to the shared renderer, hence the single instance.
	 *
	 * @param string $plugin_dir Absolute path to the plugin directory.
	 */
	public static function instance( string $plugin_dir = '' ): self {
		self::$instance ??= new self( $plugin_dir );
		return self::$instance;
	}

	/**
	 * @param string $plugin_dir Absolute path to the plugin directory.
	 */
	private function __construct( private string $plugin_dir ) {
		$this->cache = new RenderCache( wp_using_ext_object_cache() );
	}

	public function boot(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );

		( new Invalidator( $this->cache ) )->register();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'cqb', new FlushCommand( $this->cache ) );
		}
	}

	public function register_blocks(): void {
		foreach ( self::BLOCKS as $block ) {
			register_block_type( $this->plugin_dir . '/build/' . $block );
		}
	}

	public function renderer(): BlockRenderer {
		$this->renderer ??= new BlockRenderer(
			$this->cache,
			static fn( array $args ): WP_Query => new WP_Query( $args ),
			defined( 'WP_DEBUG' ) && WP_DEBUG
		);

		return $this->renderer;
	}
}
