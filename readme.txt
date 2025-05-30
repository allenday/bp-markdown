=== BP Markdown ===
Contributors: your-username
Tags: buddypress, buddyboss, markdown, content, formatting
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adds Markdown support for BuddyPress/BuddyBoss Platform content (activities, comments, etc.).

== Description ==

BP Markdown enables Markdown formatting for BuddyPress and BuddyBoss Platform content, allowing users to write rich-formatted content using simple Markdown syntax.

**Features:**
* Markdown support for activity updates
* Server-side conversion from Markdown to HTML
* Stores both original Markdown and rendered HTML
* Compatible with BuddyPress and BuddyBoss Platform

**Markdown Support Includes:**
* Headers (# ## ###)
* Bold and italic text
* Lists (ordered and unordered)
* Links and images
* Code blocks and inline code
* Tables
* And more via Markdown Extra syntax

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/bp-markdown` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. The plugin will automatically start converting Markdown content in BuddyPress/BuddyBoss activities.

== Frequently Asked Questions ==

= Does this work with BuddyBoss Platform? =

Yes! This plugin is designed to work with both BuddyPress and BuddyBoss Platform.

= What Markdown syntax is supported? =

The plugin uses PHP Markdown Extra, which supports standard Markdown plus additional features like tables, definition lists, footnotes, and more.

= Is existing content affected? =

No, existing content remains unchanged. Only new content written in Markdown will be processed.

== Changelog ==

= 0.1.0 =
* Initial release
* Markdown support for activity updates
* Server-side Markdown to HTML conversion
* Meta storage for original Markdown content 