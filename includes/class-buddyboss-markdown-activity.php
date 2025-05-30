<?php
/**
 * BuddyBoss Markdown Activity Handler Class
 *
 * @package BuddyBossMarkdown
 * @since 0.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BuddyBoss_Markdown_Activity {

    /**
     * The single instance of the class.
     *
     * @var BuddyBoss_Markdown_Activity
     * @since 0.1.0
     */
    protected static $_instance = null;

    /**
     * Meta key for storing original Markdown content for activities.
     * @var string
     */
    const ACTIVITY_META_KEY = '_buddyboss_activity_markdown_content';

    private static $original_markdown_content = ''; // For passing raw content to after_save hook

    /**
     * Main BuddyBoss_Markdown_Activity Instance.
     *
     * Ensures only one instance of BuddyBoss_Markdown_Activity is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return BuddyBoss_Markdown_Activity - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        error_log('[BuddyBoss Markdown] BuddyBoss_Markdown_Activity constructor called.');
        $this->hooks();
    }

    /**
     * Setup hooks for activity and activity comments.
     */
    private function hooks() {
        error_log('[BuddyBoss Markdown] BuddyBoss_Markdown_Activity::hooks() method CALLED.');

        if ( ! class_exists( 'BP_Activity_Activity' ) ) {
            error_log('[BuddyBoss Markdown] BP_Activity_Activity class not available in BuddyBoss_Markdown_Activity::hooks(). Cannot add activity hooks.');
            return;
        }
        error_log('[BuddyBoss Markdown] BP_Activity_Activity class IS available in BuddyBoss_Markdown_Activity::hooks(). Proceeding to add activity hooks.');

        // Use bp_before_activity_add_parse_args to capture raw content
        add_filter( 'bp_before_activity_add_parse_args', array( $this, 'prepare_activity_data_before_add' ), 5, 1 );
        
        // Use bp_activity_content_before_save to transform content to HTML *after* kses
        add_filter( 'bp_activity_content_before_save', array( $this, 'transform_content_for_database' ), 10, 1 );

        // bp_activity_after_save should still fire after bp_activity_add completes
        add_action( 'bp_activity_after_save', array( $this, 'save_original_markdown_after_activity_save' ), 10, 1 );
        
        // Display filter (currently passthrough for debugging save)
        add_filter( 'bp_get_activity_content_body', array( $this, 'display_activity_update_content' ), 8, 2 );

        error_log('[BuddyBoss Markdown] BuddyBoss_Markdown_Activity::hooks() - All hooks ADDED.');
    }

    /**
     * Get the Markdown parser instance from the core class.
     * @return \Michelf\MarkdownExtra|null
     */
    private function get_parser() {
        $core_parser = BuddyBoss_Markdown_Core::instance()->parser;
        if ( $core_parser instanceof \Michelf\MarkdownExtra ) {
            error_log('[BuddyBoss Markdown] BuddyBoss_Markdown_Activity::get_parser() - Returning VALID MarkdownExtra parser instance from Core.');
        } else {
            error_log('[BuddyBoss Markdown] BuddyBoss_Markdown_Activity::get_parser() - Parser from Core is NOT a valid MarkdownExtra instance. Type: ' . gettype($core_parser));
        }
        return $core_parser;
    }

    /**
     * Filters activity arguments before the activity item is added to the database.
     * This is where we'll grab the raw Markdown, convert it to HTML for main content,
     * and store the raw Markdown for saving later in meta via bp_activity_after_save.
     *
     * Attached to: bp_before_activity_add_parse_args
     *
     * @param array $args Arguments for bp_activity_add().
     * @return array Modified arguments.
     */
    public function prepare_activity_data_before_add( $args ) {
        error_log('[BuddyBoss Markdown] HOOK: bp_before_activity_add_parse_args CALLED.');

        $raw_markdown_source = 'args'; // Default to $args['content']
        $raw_markdown = isset($args['content']) ? $args['content'] : '';

        if ( isset( $_POST['content'] ) ) {
            $raw_markdown = wp_unslash( $_POST['content'] ); // Use wp_unslash as WP often adds slashes
            $raw_markdown_source = 'POST';
            error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - Using $_POST[\'content\'] as source for raw markdown.');
        } else if ( !isset($args['content']) ) {
            error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - Neither $_POST[\'content\'] nor $args[\'content\'] is set. Skipping.');
            self::$original_markdown_content = ''; // Ensure it's reset
            return $args;
        } else {
            error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - Using $args[\'content\'] as source for raw markdown (POST was not set).');
        }

        error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - Source for raw markdown: ' . $raw_markdown_source);
        error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - Captured raw content (JSON encoded): ' . wp_json_encode($raw_markdown));

        if ( empty( trim( $raw_markdown ) ) ) {
            error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - Raw content is empty. Skipping.');
            self::$original_markdown_content = ''; 
            return $args; 
        }
        
        // Store the original raw markdown for other hooks
        self::$original_markdown_content = $raw_markdown;
        error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - Stored to static self::$original_markdown_content (JSON encoded): ' . wp_json_encode(self::$original_markdown_content));

        // DO NOT transform to HTML here. Let it pass through for KSES.
        error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - RETURNING original $args (content not transformed yet).');
        return $args; 
    }

    /**
     * Strip outer <p> tags from content if it's wrapped in a single paragraph
     *
     * @param string $content The content to process
     * @return string The content with outer <p> tags removed if applicable
     */
    private function strip_outer_p_tags( $content ) {
        $content = trim( $content );
        error_log( 'strip_outer_p_tags() - Input: ' . wp_json_encode( $content ) );
        
        if ( empty( $content ) ) {
            error_log( 'strip_outer_p_tags() - Content is empty, returning as-is' );
            return $content;
        }

        // Create a DOMDocument to parse the HTML
        $dom = new DOMDocument();
        
        // Suppress errors for malformed HTML and use UTF-8 encoding
        libxml_use_internal_errors( true );
        $load_result = $dom->loadHTML( '<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NODEFDTD );
        $errors = libxml_get_errors();
        libxml_clear_errors();
        
        error_log( 'strip_outer_p_tags() - DOM loadHTML result: ' . var_export( $load_result, true ) );
        if ( ! empty( $errors ) ) {
            error_log( 'strip_outer_p_tags() - DOM errors: ' . wp_json_encode( array_map( function($e) { return $e->message; }, $errors ) ) );
        }

        // Get the body element (DOMDocument automatically wraps content in html/body)
        $body = $dom->getElementsByTagName( 'body' )->item( 0 );
        
        if ( ! $body ) {
            error_log( 'strip_outer_p_tags() - No body element found, returning original content' );
            return $content; // Fallback if parsing failed
        }
        
        error_log( 'strip_outer_p_tags() - Body childNodes count: ' . $body->childNodes->length );
        
        if ( $body->childNodes->length > 0 ) {
            error_log( 'strip_outer_p_tags() - First child nodeName: ' . $body->firstChild->nodeName );
        }

        // Check if there's exactly one child element and it's a <p> tag
        if ( $body->childNodes->length === 1 && $body->firstChild->nodeName === 'p' ) {
            error_log( 'strip_outer_p_tags() - Found single <p> tag, extracting inner content' );
            // Get the inner content of the <p> tag
            $inner_html = '';
            foreach ( $body->firstChild->childNodes as $child ) {
                $inner_html .= $dom->saveHTML( $child );
            }
            $result = trim( $inner_html );
            error_log( 'strip_outer_p_tags() - Extracted inner content: ' . wp_json_encode( $result ) );
            return $result;
        }

        // If it's not a single <p> tag, return original content
        error_log( 'strip_outer_p_tags() - Not a single <p> tag, returning original content' );
        return $content;
    }

    /**
     * Transform content for database storage (markdown to HTML)
     */
    public function transform_content_for_database( $content ) {
        error_log( 'transform_content_for_database() - Raw content: ' . wp_json_encode( $content ) );
        
        // Only transform if we have stored markdown content
        if ( empty( self::$original_markdown_content ) ) {
            error_log( 'transform_content_for_database() - No stored markdown content, returning as-is' );
            return $content;
        }
        
        // Strip outer <p> tags before processing
        $stripped_content = $this->strip_outer_p_tags( self::$original_markdown_content );
        error_log( 'transform_content_for_database() - After stripping p tags: ' . wp_json_encode( $stripped_content ) );
        
        $parser = BuddyBoss_Markdown_Core::instance()->parser;
        if ( ! $parser ) {
            error_log( 'transform_content_for_database() - Parser is null!' );
            return $content;
        }
        
        $html_content = $parser->transform( $stripped_content );
        error_log( 'transform_content_for_database() - Parser output: ' . wp_json_encode( $html_content ) );
        
        return $html_content;
    }

    /**
     * Saves the original Markdown content to activity meta after the activity is saved.
     *
     * Attached to: bp_activity_after_save
     *
     * @param BP_Activity_Activity $activity The activity object.
     */
    public function save_original_markdown_after_activity_save( BP_Activity_Activity $activity ) {
        error_log('[BuddyBoss Markdown] HOOK: bp_activity_after_save CALLED for activity ID: ' . (isset($activity->id) ? $activity->id : 'NOT SET'));

        if ( empty( $activity->id ) ) {
            error_log('[BuddyBoss Markdown] bp_activity_after_save - Activity ID is empty. Cannot save meta.');
            // Clear static variable if it holds content from a failed/incomplete save
            if (isset(self::$original_markdown_content)) {
                 error_log('[BuddyBoss Markdown] bp_activity_after_save - Clearing static content as activity ID was empty.');
                 self::$original_markdown_content = null;
            }
            return;
        }

        if ( isset( self::$original_markdown_content ) && self::$original_markdown_content !== null ) {
            // Strip outer <p> tags before saving to meta
            $clean_markdown = $this->strip_outer_p_tags( self::$original_markdown_content );
            error_log( 'save_original_markdown_after_activity_save() - Saving to meta: ' . wp_json_encode( $clean_markdown ) );
            
            $result = bp_activity_update_meta( $activity->id, self::ACTIVITY_META_KEY, $clean_markdown );
            error_log( 'save_original_markdown_after_activity_save() - bp_activity_update_meta result: ' . $result );

            if ($result) {
                error_log('[BuddyBoss Markdown] bp_activity_after_save - Meta successfully UPDATED for activity ID: ' . $activity->id);
            } else {
                error_log('[BuddyBoss Markdown] bp_activity_after_save - Meta update FAILED or returned falsy for activity ID: ' . $activity->id);
            }

            self::$original_markdown_content = null; 
            error_log('[BuddyBoss Markdown] bp_activity_after_save - Static content CLEARED.');
        } else {
            error_log('[BuddyBoss Markdown] bp_activity_after_save - self::$original_markdown_content is NOT SET or is NULL for activity ID: ' . $activity->id . '. Meta NOT saved/updated.');
        }
    }

    /**
     * SIMPLIFIED FOR DEBUGGING - Passthrough function.
     */
    public function display_activity_update_content( $content, $activity = null ) {
        // error_log('[BuddyBoss Markdown] display_activity_update_content (SIMPLIFIED - PASSTHROUGH) - Returning content as is.');
        return $content;
    }
} 