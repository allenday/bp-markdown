<?php
/**
 * Plugin Name: BuddyBoss Markdown
 * Description: Adds Markdown support for BuddyBoss Platform content (activities, comments, etc.).
 * Plugin URI:  [Your Plugin URI]
 * Author:      [Your Name]
 * Author URI:  [Your Author URI]
 * Version:     0.1.0
 * License:     GPLv3 or later
 * Text Domain: buddyboss-markdown
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'BUDDYBOSS_MARKDOWN_VERSION', '0.1.0' );
define( 'BUDDYBOSS_MARKDOWN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BUDDYBOSS_MARKDOWN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BUDDYBOSS_MARKDOWN_TEXT_DOMAIN', 'buddyboss-markdown' );

error_log('[BuddyBoss Markdown] Plugin file loaded and constants defined.');

/**
 * Load plugin textdomain.
 */
function buddyboss_markdown_load_textdomain() {
    load_plugin_textdomain(
        BUDDYBOSS_MARKDOWN_TEXT_DOMAIN,
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'init', 'buddyboss_markdown_load_textdomain' );

/**
 * Include Composer autoloader.
 */
if ( file_exists( BUDDYBOSS_MARKDOWN_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once BUDDYBOSS_MARKDOWN_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    // Fallback or error handling if Composer autoloader is missing.
    // This might happen if 'composer install' was not run.
    // You could display an admin notice here.
    if ( defined('WP_DEBUG') && WP_DEBUG === true ) {
        error_log( 'BuddyBoss Markdown: Composer autoloader not found. Please run "composer install".' );
    }
    // Optionally, prevent further execution if the library is critical.
    // return;
}

/**
 * Include core plugin classes.
 */
// The Markdown library itself is now loaded via Composer's autoloader.
require_once BUDDYBOSS_MARKDOWN_PLUGIN_DIR . 'includes/class-buddyboss-markdown-core.php';
// require_once BUDDYBOSS_MARKDOWN_PLUGIN_DIR . 'includes/class-buddyboss-markdown-activity.php';
// Add other content type handlers here as they are developed e.g.
// require_once BUDDYBOSS_MARKDOWN_PLUGIN_DIR . 'includes/class-buddyboss-markdown-forums.php';

/**
 * Initialize the plugin.
 */
function buddyboss_markdown_init() {
    error_log('[BuddyBoss Markdown] buddyboss_markdown_init() CALLED.');

    // Ensure the main class for the Markdown library is available before proceeding.
    if ( ! class_exists( 'Michelf\MarkdownExtra' ) ) {
        if ( defined('WP_DEBUG') && WP_DEBUG === true ) {
            error_log( 'BuddyBoss Markdown: Prerequisite Michelf\MarkdownExtra class not found. Plugin will not initialize. Check PHP Markdown library inclusion or run "composer install".' );
        }
        return; // Stop further initialization if the Markdown library isn't loaded.
    }

    // Initialize the core functionality.
    BuddyBoss_Markdown_Core::instance();

    error_log('[BuddyBoss Markdown] buddyboss_markdown_init() - Before Activity Class Check. class_exists(BuddyPress_Activity): ' . (class_exists('BuddyPress_Activity') ? 'TRUE' : 'FALSE') );
    error_log('[BuddyBoss Markdown] buddyboss_markdown_init() - Before Activity Class Check. class_exists(BP_Activity_Activity): ' . (class_exists('BP_Activity_Activity') ? 'TRUE' : 'FALSE') );

    // Initialize handlers for specific BuddyBoss components.
    // We are now ALWAYS instantiating this. The conditional logic will be inside the Activity class's hooks method.
    require_once BUDDYBOSS_MARKDOWN_PLUGIN_DIR . 'includes/class-buddyboss-markdown-activity.php';
    error_log('[BuddyBoss Markdown] buddyboss_markdown_init() - After require_once for class-buddyboss-markdown-activity.php. class_exists(BuddyBoss_Markdown_Activity): ' . (class_exists('BuddyBoss_Markdown_Activity') ? 'TRUE' : 'FALSE'));
    BuddyBoss_Markdown_Activity::instance();
    error_log('[BuddyBoss Markdown] buddyboss_markdown_init() - BuddyBoss_Markdown_Activity::instance() CALLED.');

    // Example for Forums if we had it:
    // if ( class_exists( 'bbPress' ) && class_exists( 'BP_Forums_Component' ) ) { // Check if bbPress and BuddyBoss Forums are active
    //     BuddyBoss_Markdown_Forums::instance();
    // }

    /**
     * Fires after the BuddyBoss Markdown plugin is initialized.
     *
     * @since 0.1.0
     */
    do_action( 'buddyboss_markdown_initialized' );
}
// add_action( 'bp_init', 'buddyboss_markdown_init', 20 ); // Default BuddyPress hook

error_log('[BuddyBoss Markdown] Main plugin file - BEFORE add_action for bp_init.');
// add_action( 'bp_ready', 'buddyboss_markdown_init', 99 );
add_action( 'bp_init', 'buddyboss_markdown_init', 20 );
error_log('[BuddyBoss Markdown] Main plugin file - AFTER add_action for bp_init.');

/**
 * Plugin activation hook.
 */
function buddyboss_markdown_activate() {
    error_log('[BuddyBoss Markdown] buddyboss_markdown_activate() CALLED.');
    // Activation tasks (e.g., set default options)
}
register_activation_hook( __FILE__, 'buddyboss_markdown_activate' );

/**
 * Plugin deactivation hook.
 */
function buddyboss_markdown_deactivate() {
    error_log('[BuddyBoss Markdown] buddyboss_markdown_deactivate() CALLED.');
    // Deactivation tasks (e.g., clear caches or transients if any)
}
register_deactivation_hook( __FILE__, 'buddyboss_markdown_deactivate' );

// Add a link to plugin settings page from Plugins page
function buddyboss_markdown_add_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=buddyboss-markdown-settings">' . __( 'Settings', BUDDYBOSS_MARKDOWN_TEXT_DOMAIN ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
// add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), 'buddyboss_markdown_add_settings_link' );

// Basic Settings Page (Placeholder - implement in a separate admin class later)
function buddyboss_markdown_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p><?php _e( 'Settings for BuddyBoss Markdown will go here.', BUDDYBOSS_MARKDOWN_TEXT_DOMAIN ); ?></p>
        <?php
        // Example: Option for enabling/disabling Markdown on certain components
        // Example: Option for choosing Markdown parser (Standard, Extra, etc.)
        ?>
    </div>
    <?php
}

function buddyboss_markdown_register_settings_page() {
    // add_options_page(
    //     __( 'BuddyBoss Markdown Settings', BUDDYBOSS_MARKDOWN_TEXT_DOMAIN ),
    //     __( 'BuddyBoss Markdown', BUDDYBOSS_MARKDOWN_TEXT_DOMAIN ),
    //     'manage_options',
    //     'buddyboss-markdown-settings',
    //     'buddyboss_markdown_settings_page'
    // );
}
// add_action( 'admin_menu', 'buddyboss_markdown_register_settings_page' );

/**
 * Plugin uninstall hook.
 */
function buddyboss_markdown_uninstall() {
    // Uninstall tasks (e.g., delete options, remove custom tables if any)
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        die;
    }
    // Example: delete_option( 'buddyboss_markdown_settings' );
}
// No register_uninstall_hook here, it's handled by uninstall.php 