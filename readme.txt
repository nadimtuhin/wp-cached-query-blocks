=== Cached Query Blocks ===
Contributors: nadimtuhin
Tags: blocks, cache, latest posts, related posts, object cache
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Post list and related posts blocks whose rendered HTML is cached and invalidated when content changes.

== Description ==

Dynamic post list blocks run a database query on every page view. These blocks store their rendered HTML in the object cache (or in transients when no persistent object cache is installed) and invalidate it when posts or terms change.

* Cached Post List: recent posts of a public post type, optionally limited to one term.
* Cached Related Posts: posts that share a term with the current post.

Run `wp cqb flush` to invalidate every cached render.

== Installation ==

1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate it in the Plugins screen.
3. Add the "Cached Post List" or "Cached Related Posts" block in the editor.

== Changelog ==

= 0.1.0 =
* First release.
