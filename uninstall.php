<?php
/**
 * Uninstall BP Markdown
 *
 * @package BPMarkdown
 * @since 0.1.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Here you can define what happens when the plugin is deleted.
// For example, you could remove any custom tables, options, or metadata that the plugin created.

// Example: Remove all activity meta created by this plugin
// global $wpdb;
// $wpdb->query( "DELETE FROM {$wpdb->bp_activity_meta} WHERE meta_key = '_bp_activity_markdown_content'" );

// Example: Remove plugin options
// delete_option( 'bp_markdown_settings' ); 