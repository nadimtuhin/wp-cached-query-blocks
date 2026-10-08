<?php
/**
 * Stores rendered block HTML.
 *
 * @package CachedQueryBlocks
 */

namespace CachedQueryBlocks\Cache;

/**
 * Reads and writes rendered HTML, keyed by block, attributes, context and a salt.
 *
 * Invalidation never deletes entries. Bumping the salt changes every key, so old
 * entries are simply never read again and expire on their own TTL. This is the
 * same approach core uses with wp_cache_set_last_changed().
 */
final class RenderCache {

	public const GROUP = 'cqb';

	public const MIN_TTL = 60;

	public const MAX_TTL = DAY_IN_SECONDS;

	private const SALT_CACHE_KEY = 'last_changed';

	private const SALT_OPTION = 'cqb_last_changed';

	/**
	 * @param bool $persistent_object_cache True when a persistent object cache (Redis, Memcached) is active.
	 */
	public function __construct( private bool $persistent_object_cache ) {}

	public static function clamp_ttl( int $ttl ): int {
		return max( self::MIN_TTL, min( self::MAX_TTL, $ttl ) );
	}

	/**
	 * @param string               $block_name Block name, e.g. cached-query-blocks/post-list.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param array<string, mixed> $context    Anything else that changes the output, such as the current post ID.
	 */
	public function key( string $block_name, array $attributes, array $context = array() ): string {
		$parts = array(
			$block_name,
			self::sort_keys( $attributes ),
			self::sort_keys( $context ),
			$this->salt(),
		);

		return 'cqb_' . md5( (string) wp_json_encode( $parts ) );
	}

	public function get( string $key ): ?string {
		$html = $this->persistent_object_cache
			? wp_cache_get( $key, self::GROUP )
			: get_transient( $key );

		return is_string( $html ) ? $html : null;
	}

	public function set( string $key, string $html, int $ttl ): void {
		$ttl = self::clamp_ttl( $ttl );

		if ( $this->persistent_object_cache ) {
			wp_cache_set( $key, $html, self::GROUP, $ttl );
			return;
		}

		set_transient( $key, $html, $ttl );
	}

	public function salt(): string {
		$salt = $this->persistent_object_cache
			? wp_cache_get( self::SALT_CACHE_KEY, self::GROUP )
			: get_option( self::SALT_OPTION );

		if ( ! is_string( $salt ) || '' === $salt ) {
			$salt = $this->bump_salt();
		}

		return $salt;
	}

	/**
	 * Without a persistent object cache the salt lives in an option, because the
	 * default object cache forgets everything at the end of the request while
	 * transients survive.
	 */
	public function bump_salt(): string {
		$salt = microtime();

		if ( $this->persistent_object_cache ) {
			wp_cache_set( self::SALT_CACHE_KEY, $salt, self::GROUP );
		} else {
			update_option( self::SALT_OPTION, $salt, true );
		}

		return $salt;
	}

	/**
	 * @param array<mixed> $values Values to sort.
	 * @return array<mixed>
	 */
	private static function sort_keys( array $values ): array {
		ksort( $values );

		foreach ( $values as $name => $value ) {
			if ( is_array( $value ) ) {
				$values[ $name ] = self::sort_keys( $value );
			}
		}

		return $values;
	}
}
