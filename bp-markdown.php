<?php
/**
 * Plugin Name: BP Markdown
 * Description: Adds Markdown support for BuddyPress/BuddyBoss Platform content (activities, comments, etc.).
 * Plugin URI:  https://github.com/allenday/bp-markdown
 * Author:      Allen Day
 * Author URI:  https://github.com/allenday
 * Version:     0.1.0
 * License:     GPLv3 or later
 * Text Domain: bp-markdown
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'BP_MARKDOWN_VERSION', '0.1.0' );
define( 'BP_MARKDOWN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BP_MARKDOWN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BP_MARKDOWN_TEXT_DOMAIN', 'bp-markdown' );

/**
 * Load plugin textdomain.
 */
function bp_markdown_load_textdomain() {
    load_plugin_textdomain(
        BP_MARKDOWN_TEXT_DOMAIN,
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'init', 'bp_markdown_load_textdomain' );

/**
 * Include Composer autoloader.
 */
if ( file_exists( BP_MARKDOWN_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once BP_MARKDOWN_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    // Fallback or error handling if Composer autoloader is missing.
    if ( defined('WP_DEBUG') && WP_DEBUG === true ) {
        error_log( 'BP Markdown: Composer autoloader not found. Please run "composer install".' );
    }
}

/**
 * Include core plugin classes.
 */
require_once BP_MARKDOWN_PLUGIN_DIR . 'includes/class-bp-markdown-core.php';

/**
 * Initialize the plugin.
 */
function bp_markdown_init() {
    // Ensure the main class for the Markdown library is available before proceeding.
    if ( ! class_exists( 'Michelf\MarkdownExtra' ) ) {
        if ( defined('WP_DEBUG') && WP_DEBUG === true ) {
            error_log( 'BP Markdown: Prerequisite Michelf\MarkdownExtra class not found. Plugin will not initialize. Check PHP Markdown library inclusion or run "composer install".' );
        }
        return; // Stop further initialization if the Markdown library isn't loaded.
    }

    // Initialize the core functionality.
    BP_Markdown_Core::instance();

    // Initialize handlers for specific BuddyPress/BuddyBoss components.
    require_once BP_MARKDOWN_PLUGIN_DIR . 'includes/class-bp-markdown-activity.php';
    BP_Markdown_Activity::instance();

    /**
     * Fires after the BP Markdown plugin is initialized.
     *
     * @since 0.1.0
     */
    do_action( 'bp_markdown_initialized' );
}
add_action( 'bp_init', 'bp_markdown_init', 20 );

/**
 * Plugin activation hook.
 */
function bp_markdown_activate() {
    // Activation tasks (e.g., set default options)
}
register_activation_hook( __FILE__, 'bp_markdown_activate' );

/**
 * Plugin deactivation hook.
 */
function bp_markdown_deactivate() {
    // Deactivation tasks (e.g., clear caches or transients if any)
}
register_deactivation_hook( __FILE__, 'bp_markdown_deactivate' );

/**
 * Plugin uninstall hook.
 */
function bp_markdown_uninstall() {
    // Uninstall tasks (e.g., delete options, remove custom tables if any)
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        die;
    }
    // Example: delete_option( 'bp_markdown_settings' );
} 