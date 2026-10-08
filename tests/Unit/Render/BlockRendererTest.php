<?php
/**
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Tests\Unit\Render;

use Brain\Monkey\Functions;
use CachedQueryBlocks\Cache\RenderCache;
use CachedQueryBlocks\Render\BlockRenderer;
use CachedQueryBlocks\Tests\Unit\TestCase;
use WP_Post;
use WP_Query;

final class BlockRendererTest extends TestCase {

	private const BLOCK = 'cached-query-blocks/post-list';

	private int $queries_run = 0;

	/** @var array<int, WP_Post> */
	private array $posts_to_return = array();

	protected function setUp(): void {
		parent::setUp();

		$this->posts_to_return = array( new WP_Post( 1 ), new WP_Post( 2 ) );

		Functions\when( 'get_permalink' )->alias( fn( WP_Post $post ) => 'https://example.test/?p=' . $post->ID );
		Functions\when( 'get_the_title' )->alias( fn( WP_Post $post ) => 'Post <' . $post->ID . '>' );
		Functions\when( 'get_the_excerpt' )->justReturn( '<p>Excerpt</p><script>x</script>' );
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_html' )->alias( fn( $text ) => htmlspecialchars( $text, ENT_QUOTES ) );
		Functions\when( 'wp_kses_post' )->alias( fn( $html ) => strip_tags( $html, '<p>' ) );
	}

	private function renderer( bool $debug = false ): BlockRenderer {
		$query_factory = function ( array $args ): WP_Query {
			++$this->queries_run;
			$query        = new WP_Query();
			$query->posts = $this->posts_to_return;
			return $query;
		};

		return new BlockRenderer( new RenderCache( true ), $query_factory, $debug );
	}

	private function render( BlockRenderer $renderer, array $attributes = array() ): string {
		return $renderer->render( self::BLOCK, $attributes, array(), fn() => array( 'post_type' => 'post' ) );
	}

	public function test_miss_runs_the_query_and_escapes_output(): void {
		$html = $this->render( $this->renderer(), array( 'showExcerpt' => true ) );

		$this->assertSame( 1, $this->queries_run );
		$this->assertStringContainsString( '<a class="cqb-post__link" href="https://example.test/?p=1">Post &lt;1&gt;</a>', $html );
		$this->assertStringContainsString( '<p>Excerpt</p>x', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_password_protected_excerpt_is_never_rendered(): void {
		$this->posts_to_return = array( new WP_Post( 1, 'secret' ) );

		$html = $this->render( $this->renderer(), array( 'showExcerpt' => true ) );

		$this->assertStringContainsString( 'Post &lt;1&gt;', $html );
		$this->assertStringNotContainsString( 'Excerpt', $html );
	}

	public function test_hit_path_does_not_query(): void {
		$renderer = $this->renderer();

		$first  = $this->render( $renderer );
		$second = $this->render( $renderer );

		$this->assertSame( 1, $this->queries_run );
		$this->assertSame( $first, $second );
	}

	public function test_invalid_args_render_nothing_without_querying(): void {
		$html = $this->renderer()->render( self::BLOCK, array(), array(), fn() => null );

		$this->assertSame( '', $html );
		$this->assertSame( 0, $this->queries_run );
	}

	public function test_debug_comment_reports_miss_then_hit(): void {
		$renderer = $this->renderer( true );

		$this->assertStringEndsWith( '<!-- cqb: miss -->', $this->render( $renderer ) );
		$this->assertStringEndsWith( '<!-- cqb: hit -->', $this->render( $renderer ) );
	}

	public function test_no_debug_comment_when_debug_is_off(): void {
		$this->assertStringNotContainsString( 'cqb:', $this->render( $this->renderer() ) );
	}
}
