<?php
/**
 * BP Markdown Activity Handler Class
 *
 * @package BPMarkdown
 * @since 0.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BP_Markdown_Activity {

    /**
     * The single instance of the class.
     *
     * @var BP_Markdown_Activity
     * @since 0.1.0
     */
    protected static $_instance = null;

    /**
     * Meta key for storing original Markdown content for activities.
     * @var string
     */
    const ACTIVITY_META_KEY = '_bp_activity_markdown_content';

    /**
     * Static property to pass raw content between hooks
     * @var string
     */
    private static $original_markdown_content = '';

    /**
     * Main BP_Markdown_Activity Instance.
     *
     * Ensures only one instance of BP_Markdown_Activity is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return BP_Markdown_Activity - Main instance.
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
        $this->hooks();
    }

    /**
     * Setup hooks for activity and activity comments.
     */
    private function hooks() {
        if ( ! class_exists( 'BP_Activity_Activity' ) ) {
            return;
        }

        // Use bp_before_activity_add_parse_args to capture raw content
        add_filter( 'bp_before_activity_add_parse_args', array( $this, 'prepare_activity_data_before_add' ), 5, 1 );
        
        // Use bp_activity_content_before_save to transform content to HTML after kses
        add_filter( 'bp_activity_content_before_save', array( $this, 'transform_content_for_database' ), 10, 1 );

        // bp_activity_after_save to save original markdown to meta
        add_action( 'bp_activity_after_save', array( $this, 'save_original_markdown_after_activity_save' ), 10, 1 );
        
        // Display filter
        add_filter( 'bp_get_activity_content_body', array( $this, 'display_activity_update_content' ), 8, 2 );
    }

    /**
     * Filters activity arguments before the activity item is added to the database.
     * This is where we'll grab the raw Markdown content.
     *
     * Attached to: bp_before_activity_add_parse_args
     *
     * @param array $args Arguments for bp_activity_add().
     * @return array Modified arguments.
     */
    public function prepare_activity_data_before_add( $args ) {
        $raw_markdown = isset($args['content']) ? $args['content'] : '';

        if ( isset( $_POST['content'] ) ) {
            $raw_markdown = wp_unslash( $_POST['content'] );
        } else if ( !isset($args['content']) ) {
            self::$original_markdown_content = '';
            return $args;
        }

        if ( empty( trim( $raw_markdown ) ) ) {
            self::$original_markdown_content = ''; 
            return $args; 
        }
        
        // Store the original raw markdown for other hooks
        self::$original_markdown_content = $raw_markdown;

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
        
        if ( empty( $content ) ) {
            return $content;
        }

        // Create a DOMDocument to parse the HTML
        $dom = new DOMDocument();
        
        // Suppress errors for malformed HTML and use UTF-8 encoding
        libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NODEFDTD );
        libxml_clear_errors();

        // Get the body element (DOMDocument automatically wraps content in html/body)
        $body = $dom->getElementsByTagName( 'body' )->item( 0 );
        
        if ( ! $body ) {
            return $content; // Fallback if parsing failed
        }

        // Check if there's exactly one child element and it's a <p> tag
        if ( $body->childNodes->length === 1 && $body->firstChild->nodeName === 'p' ) {
            // Get the inner content of the <p> tag
            $inner_html = '';
            foreach ( $body->firstChild->childNodes as $child ) {
                $inner_html .= $dom->saveHTML( $child );
            }
            return trim( $inner_html );
        }

        // If it's not a single <p> tag, return original content
        return $content;
    }

    /**
     * Transform content for database storage (markdown to HTML)
     */
    public function transform_content_for_database( $content ) {
        // Only transform if we have stored markdown content
        if ( empty( self::$original_markdown_content ) ) {
            return $content;
        }
        
        // Strip outer <p> tags before processing
        $stripped_content = $this->strip_outer_p_tags( self::$original_markdown_content );
        
        $parser = BP_Markdown_Core::instance()->parser;
        if ( ! $parser ) {
            return $content;
        }
        
        $html_content = $parser->transform( $stripped_content );
        
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
        if ( empty( $activity->id ) ) {
            if (isset(self::$original_markdown_content)) {
                 self::$original_markdown_content = null;
            }
            return;
        }

        if ( isset( self::$original_markdown_content ) && self::$original_markdown_content !== null ) {
            // Strip outer <p> tags before saving to meta
            $clean_markdown = $this->strip_outer_p_tags( self::$original_markdown_content );
            
            bp_activity_update_meta( $activity->id, self::ACTIVITY_META_KEY, $clean_markdown );

            self::$original_markdown_content = null; 
        }
    }

    /**
     * Display activity content (currently passthrough)
     */
    public function display_activity_update_content( $content, $activity = null ) {
        return $content;
    }
} 