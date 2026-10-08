# Cached Query Blocks

Two dynamic blocks, a post list and a related posts list, whose server-rendered HTML is cached and invalidated when content changes.

Requires WordPress 6.5+ and PHP 8.1+.

## The problem

Dynamic "latest posts" style blocks run a `WP_Query` on every page view. On a busy site that is the same query, with the same result, thousands of times between edits. A full-page cache hides this for anonymous visitors, but logged-in users, cache misses, and pages with a short page-cache TTL still pay for it.

This plugin caches the rendered HTML of each block instance and throws it away only when content that could change it is saved.

## Blocks

- **Cached Post List** (`cached-query-blocks/post-list`): posts of one public post type, optionally filtered to a single term. Settings: post type, taxonomy and term, number of posts (1 to 20), show excerpt, cache lifetime.
- **Cached Related Posts** (`cached-query-blocks/related-posts`): posts of the same type that share at least one public term with the current post. Settings: number of posts, show excerpt, cache lifetime.

Both render a plain `<ul class="cqb-posts">` list with titles, links and optional excerpts.

## How caching works

1. On render, the plugin builds a cache key: `md5` of the block name, the block attributes with keys sorted (so attribute order does not matter), any context that changes the output (the current post ID for related posts), and a salt.
2. If a persistent object cache is active (`wp_using_ext_object_cache()`, for example Redis or Memcached), the HTML is stored with `wp_cache_set()` in the `cqb` group. Otherwise it is stored as a transient.
3. On a hit, the stored HTML is returned and no `WP_Query` runs. On a miss, the query runs, the HTML is built and escaped, then stored.
4. The TTL comes from the block's "Cache lifetime" setting, clamped to 60 seconds through 24 hours.
5. When something visitors could see changes, the salt is replaced with a new value. Every key changes, so old entries are never read again and expire on their own TTL. Nothing is flushed or deleted. This is the same "last changed" approach core uses for its own query caches. The triggers are:
   - `save_post`, `deleted_post` and `set_object_terms` for published posts of a viewable post type. Drafts, revisions, autosaves, menu items and other non-viewable types are ignored.
   - `transition_post_status` when a post enters or leaves `publish` (publish, unpublish, trash).
   - `edited_term`.
   - Changes to `permalink_structure`, `category_base`, `tag_base`, `home` and `siteurl`, because the cached HTML contains absolute links.
6. The salt lives in the object cache when it is persistent. Without one it lives in an autoloaded option, because the default object cache does not survive the request while transients do.

Excerpts of password-protected posts are never rendered, because `get_the_excerpt()` reads the visitor's password cookie and the cached HTML is shared by everyone.

### Known limits

- The invalidation is coarse: any qualifying change invalidates every cached block. That keeps it correct without tracking which posts each block shows. On sites that publish many times a minute the hit rate will be low; per-post-type salts would be the next step.
- The key does not include the locale, the request scheme or the host. Multilingual sites (WPML, Polylang) and sites that serve both `http` and `https` would share one cached render across those variants.
- Without a persistent object cache, entries under an old salt stay in `wp_options` until core's daily `delete_expired_transients` job removes them. The related posts block stores one entry per post, so a busy site without Redis or Memcached can build up a lot of rows between cron runs.

With `WP_DEBUG` on, each rendered block ends with `<!-- cqb: hit -->` or `<!-- cqb: miss -->`.

### WP-CLI

```sh
wp cqb flush
```

Replaces the salt, which invalidates every cached render.

## Query details

- `no_found_rows => true` (no pagination, so no `SQL_CALC_FOUND_ROWS`).
- `ignore_sticky_posts => true`.
- `update_post_meta_cache => false`. The markup never reads post meta.
- `update_post_term_cache` is only enabled when the permalink structure contains `%category%`, because `get_permalink()` then needs each post's terms.
- The post type must be registered and viewable on the front end (`is_post_type_viewable()`), the same rule the editor uses to fill its post type list. A taxonomy filter must belong to that post type. Anything else renders nothing.

## Benchmark

I have not published numbers because they depend on the database, the object cache backend, and the content. Here is how to measure on your own site.

With WP-CLI, inside a site that has the plugin active and some published posts:

```sh
wp eval-file wp-content/plugins/cached-query-blocks/bin/bench.php 200
```

It renders the post list block 200 times with the cache invalidated before each render (miss), then 200 times warm (hit), and prints average milliseconds and database queries per render for each mode. On a miss it also resets core's `posts` last-changed value, so core's own `WP_Query` cache cannot answer instead. Without a persistent object cache it clears the in-memory cache before each render to match a fresh page view, so a hit still shows the few queries needed to read the salt option and the transient.

With Query Monitor:

1. Add a Cached Post List block to a page and turn on `WP_DEBUG`.
2. Load the page once, then reload it. The HTML source should show `<!-- cqb: miss -->` then `<!-- cqb: hit -->`.
3. Compare the "Queries by Component" panel for the plugin between the two loads. The `WP_Query` for the block should appear on the miss and be absent on the hit.

## Development

```sh
composer install
composer test   # PHPUnit with Brain Monkey, no WordPress or database needed
composer lint   # PHPCS with WordPress Coding Standards

npm install
npm run build
npm run lint:js
npm run test:js
```

Block sources live in `blocks/`, PHP classes in `src/` (PSR-4, `CachedQueryBlocks\`). `build/` is not committed; run `npm run build` before activating the plugin from a git checkout.

## License

GPL-2.0-or-later.
