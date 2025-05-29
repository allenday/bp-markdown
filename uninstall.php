<?php
/**
 * BuddyBoss Markdown Uninstall
 *
 * Uninstalls the plugin and cleans up options, etc.
 *
 * @package BuddyBossMarkdown
 * @since 0.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// If you have stored any options, delete them here.
// Example:
// delete_option( 'buddyboss_markdown_settings' );

// If you have created custom tables, drop them here.
// Example:
// global $wpdb;
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_custom_table" );

// Clear any cached data if necessary.

// Note: Be careful when removing data. Users might not want their Markdown content (if stored separately)
// to be deleted if they are just temporarily uninstalling the plugin.
// Consider making data removal an option or a separate process. 