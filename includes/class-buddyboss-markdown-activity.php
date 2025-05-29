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
     * @return \\Michelf\\MarkdownExtra|null
     */
    private function get_parser() {
        return BuddyBoss_Markdown_Core::instance()->parser;
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

        if ( !isset($args['content']) ) {
            error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - $args[\'content\'] is NOT SET. Skipping.');
            self::$original_markdown_content = ''; // Ensure it's reset
            return $args;
        }

        $raw_markdown = $args['content'];
        error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - Incoming raw content from $args[\'content\']: ' . substr(sanitize_text_field($raw_markdown), 0, 300));

        if ( empty( trim( $raw_markdown ) ) ) {
            error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - Raw content is empty. Skipping.');
            self::$original_markdown_content = ''; 
            return $args; 
        }
        
        // Store the original raw markdown for other hooks
        self::$original_markdown_content = $raw_markdown;
        error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - Stored to static self::$original_markdown_content: ' . substr(sanitize_text_field(self::$original_markdown_content),0, 300));

        // DO NOT transform to HTML here. Let it pass through for KSES.
        error_log('[BuddyBoss Markdown] bp_before_activity_add_parse_args - RETURNING original $args (content not transformed yet).');
        return $args; 
    }

    /**
     * Transforms the activity content to HTML using the stored raw Markdown.
     * This runs after KSES, so our HTML should be final.
     *
     * Attached to: bp_activity_content_before_save
     *
     * @param string $content The content string (potentially KSES'd).
     * @return string The transformed HTML content.
     */
    public function transform_content_for_database( $content ) {
        error_log('[BuddyBoss Markdown] HOOK: bp_activity_content_before_save CALLED.');
        error_log('[BuddyBoss Markdown] bp_activity_content_before_save - Incoming content (potentially KSES\'d from original): ' . substr(sanitize_text_field($content), 0, 300));

        if ( empty(self::$original_markdown_content) ) {
            error_log('[BuddyBoss Markdown] bp_activity_content_before_save - self::$original_markdown_content is EMPTY. Cannot transform. Returning $content as is.');
            return $content;
        }

        $raw_markdown_to_transform = self::$original_markdown_content;
        error_log('[BuddyBoss Markdown] bp_activity_content_before_save - Using self::$original_markdown_content for transformation: ' . substr(sanitize_text_field($raw_markdown_to_transform), 0, 300));
        
        $parser = $this->get_parser();
        if ( ! $parser ) {
            error_log('[BuddyBoss Markdown] bp_activity_content_before_save - Parser NOT available. Returning original $content unchanged.');
            // Potentially clear self::$original_markdown_content if it won't be used by save_original_markdown_after_activity_save
            // For now, let's leave it for the after_save hook.
            return $content;
        }

        // Transform the original raw markdown to HTML
        $html_content = $parser->transform( $raw_markdown_to_transform );
        error_log('[BuddyBoss Markdown] bp_activity_content_before_save - Transformed original markdown to HTML: ' . substr(sanitize_text_field($html_content), 0, 300));
        error_log('[BuddyBoss Markdown] bp_activity_content_before_save - RETURNING this new HTML content to be saved.');
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
            $markdown_to_save = self::$original_markdown_content;
            error_log('[BuddyBoss Markdown] bp_activity_after_save - Found static content. Length: ' . strlen($markdown_to_save) . ' chars. Content: ' . substr(sanitize_text_field($markdown_to_save), 0, 200) . ' for activity ID: ' . $activity->id);
            
            error_log('[BuddyBoss Markdown] bp_activity_after_save - BEFORE calling bp_activity_update_meta. Activity ID: ' . $activity->id . ', Meta Key: ' . self::ACTIVITY_META_KEY . ', Value to save: ' . substr(sanitize_text_field($markdown_to_save), 0, 200));
            $meta_update_result = bp_activity_update_meta( $activity->id, self::ACTIVITY_META_KEY, $markdown_to_save );
            error_log('[BuddyBoss Markdown] bp_activity_after_save - AFTER calling bp_activity_update_meta. Result: ' . var_export($meta_update_result, true) . ' for activity ID: ' . $activity->id);

            if ($meta_update_result) {
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