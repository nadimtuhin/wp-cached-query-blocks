<?php
/**
 * Renders a list of posts through the render cache.
 *
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Render;

use CachedQueryBlocks\Cache\RenderCache;
use Closure;
use WP_Post;
use WP_Query;

/**
 * Returns cached HTML when available, otherwise runs the query and caches the result.
 */
final class BlockRenderer {

	/**
	 * @param RenderCache $cache         Cache for rendered HTML.
	 * @param Closure     $query_factory Receives WP_Query args and returns a WP_Query. Injected so tests can prove the hit path never queries.
	 * @param bool        $debug         Appends a hit or miss HTML comment.
	 */
	public function __construct(
		private RenderCache $cache,
		private Closure $query_factory,
		private bool $debug = false
	) {}

	/**
	 * @param string               $block_name Block name.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param array<string, mixed> $context    Extra values that change the output.
	 * @param callable             $build_args Returns WP_Query args or null. Only called on a cache miss.
	 */
	public function render( string $block_name, array $attributes, array $context, callable $build_args ): string {
		$key    = $this->cache->key( $block_name, $attributes, $context );
		$html   = $this->cache->get( $key );
		$status = 'hit';

		if ( null === $html ) {
			$status = 'miss';
			$args   = $build_args();
			$html   = null === $args ? '' : $this->render_posts( ( $this->query_factory )( $args ), ! empty( $attributes['showExcerpt'] ) );

			$this->cache->set( $key, $html, (int) ( $attributes['cacheTtl'] ?? 0 ) );
		}

		if ( $this->debug && '' !== $html ) {
			$html .= '<!-- cqb: ' . $status . ' -->';
		}

		return $html;
	}

	private function render_posts( WP_Query $query, bool $show_excerpt ): string {
		$posts = array_filter( $query->posts, static fn( $post ): bool => $post instanceof WP_Post );

		if ( array() === $posts ) {
			return '';
		}

		$items = '';

		foreach ( $posts as $post ) {
			// get_the_excerpt() unlocks protected posts from the visitor's password cookie, which the shared cache must never store.
			$excerpt = $show_excerpt && '' === $post->post_password
				? '<div class="cqb-post__excerpt">' . wp_kses_post( get_the_excerpt( $post ) ) . '</div>'
				: '';

			$items .= sprintf(
				'<li class="cqb-post"><a class="cqb-post__link" href="%1$s">%2$s</a>%3$s</li>',
				esc_url( (string) get_permalink( $post ) ),
				esc_html( get_the_title( $post ) ),
				$excerpt
			);
		}

		return '<ul class="cqb-posts">' . $items . '</ul>';
	}
}
