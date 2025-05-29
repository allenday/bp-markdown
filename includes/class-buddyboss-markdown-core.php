<?php
/**
 * BuddyBoss Markdown Core Class
 *
 * @package BuddyBossMarkdown
 * @since 0.1.0
 */

use Michelf\MarkdownExtra;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BuddyBoss_Markdown_Core {

    /**
     * The single instance of the class.
     *
     * @var BuddyBoss_Markdown_Core
     * @since 0.1.0
     */
    protected static $_instance = null;

    /**
     * Markdown parser instance.
     *
     * @var MarkdownExtra
     * @since 0.1.0
     */
    public $parser = null;

    /**
     * Main BuddyBoss_Markdown_Core Instance.
     *
     * Ensures only one instance of BuddyBoss_Markdown_Core is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return BuddyBoss_Markdown_Core - Main instance.
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
        error_log('[BuddyBoss Markdown] BuddyBoss_Markdown_Core constructor called.');
        $this->setup_parser();
        $this->hooks();
    }

    /**
     * Setup the Markdown parser.
     */
    private function setup_parser() {
        if ( ! class_exists( 'Michelf\MarkdownExtra' ) ) {
            // This should not happen if the lib files are included correctly.
            // Add some error logging or admin notice here if needed.
            return;
        }
        // Initialize the parser
        $this->parser = new MarkdownExtra;

        // Configure parser options (optional - defaults are usually fine)
        // Example: $this->parser->hard_wrap = true;

        /**
         * Filter the Markdown parser instance after it has been initialized.
         *
         * @since 0.1.0
         * @param MarkdownExtra $parser The Markdown parser instance.
         */
        $this->parser = apply_filters( 'buddyboss_markdown_parser_instance', $this->parser );
    }

    /**
     * Setup hooks.
     */
    private function hooks() {
        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Add allowed HTML tags (important for wp_kses)
        // We might need to adjust this based on BuddyBoss's KSES handling
        add_filter( 'bp_kses_allowed_tags', array( $this, 'add_allowed_tags' ), 10, 1 ); // For BuddyPress general KSES
        // add_filter( 'bbp_kses_allowed_tags', array( $this, 'add_allowed_tags' ) ); // If also targeting bbPress through BuddyBoss

    }

    /**
     * Convert Markdown to HTML.
     *
     * @param string $markdown The Markdown content.
     * @return string HTML content.
     */
    public function markdown_to_html( $markdown ) {
        if ( ! $this->parser ) {
            return $markdown; // Or handle error appropriately
        }

        /**
         * Filter the Markdown content before it is transformed to HTML.
         *
         * @since 0.1.0
         * @param string $markdown The original Markdown content.
         */
        $markdown = apply_filters( 'buddyboss_markdown_pre_transform', $markdown );

        $html = $this->parser->transform( $markdown );

        /**
         * Filter the HTML content after it has been transformed from Markdown.
         *
         * @since 0.1.0
         * @param string $html The transformed HTML content.
         * @param string $markdown The original Markdown content.
         */
        $html = apply_filters( 'buddyboss_markdown_post_transform', $html, $markdown );

        return $html;
    }

    /**
     * Enqueue scripts and styles for the frontend.
     */
    public function enqueue_scripts() {
        // Example: if we add a CSS for styling Markdown output or JS for a client-side editor preview
        // wp_enqueue_style( 'buddyboss-markdown-frontend', BUDDYBOSS_MARKDOWN_PLUGIN_URL . 'assets/css/frontend.css', array(), BUDDYBOSS_MARKDOWN_VERSION );
        // wp_enqueue_script( 'buddyboss-markdown-frontend', BUDDYBOSS_MARKDOWN_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), BUDDYBOSS_MARKDOWN_VERSION, true );

        // Localize script if needed
        // wp_localize_script( 'buddyboss-markdown-frontend', 'bbMarkdownData', array(
        //     'ajax_url' => admin_url( 'admin-ajax.php' ),
        //     'nonce'    => wp_create_nonce( 'buddyboss_markdown_nonce' )
        // ));
    }

    /**
     * Enqueue scripts and styles for the admin area (if needed).
     */
    public function enqueue_admin_scripts() {
        // Example: for plugin settings page
        // wp_enqueue_style( 'buddyboss-markdown-admin', BUDDYBOSS_MARKDOWN_PLUGIN_URL . 'assets/css/admin.css', array(), BUDDYBOSS_MARKDOWN_VERSION );
        // wp_enqueue_script( 'buddyboss-markdown-admin', BUDDYBOSS_MARKDOWN_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), BUDDYBOSS_MARKDOWN_VERSION, true );
    }

    /**
     * Add additional HTML tags allowed by wp_kses for Markdown output.
     *
     * @param array $tags Allowed tags array.
     * @return array Modified tags array.
     */
    public function add_allowed_tags( $tags ) {
        $markdown_tags = array(
            'p'     => array(),
            'hr'    => array(),
            'table' => array( 'class' => true, 'id' => true, 'style' => true ),
            'thead' => array( 'class' => true, 'id' => true, 'style' => true ),
            'tbody' => array( 'class' => true, 'id' => true, 'style' => true ),
            'tfoot' => array( 'class' => true, 'id' => true, 'style' => true ),
            'th'    => array( 'class' => true, 'id' => true, 'style' => true, 'colspan' => true, 'rowspan' => true, 'align' => true ),
            'tr'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'td'    => array( 'class' => true, 'id' => true, 'style' => true, 'colspan' => true, 'rowspan' => true, 'align' => true ),
            'h1'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'h2'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'h3'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'h4'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'h5'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'h6'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'pre'   => array( 'class' => true, 'id' => true, 'style' => true ),
            'code'  => array( 'class' => true, 'id' => true, 'style' => true ),
            // Add other tags as needed by Markdown Extra (e.g., dl, dt, dd, abbr, sup, sub, etc.)
            'dl'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'dt'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'dd'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'abbr'  => array( 'title' => true, 'class' => true, 'id' => true, 'style' => true ),
            'sup'   => array( 'class' => true, 'id' => true, 'style' => true ),
            'sub'   => array( 'class' => true, 'id' => true, 'style' => true ),
            'img'   => array( 'src' => true, 'alt' => true, 'title' => true, 'width' => true, 'height' => true, 'class' => true, 'id' => true, 'style' => true ),
        );

        // Merge with existing tags. Be careful not to overwrite essential restrictions.
        foreach ( $markdown_tags as $tag => $attrs ) {
            if ( ! isset( $tags[ $tag ] ) ) {
                $tags[ $tag ] = $attrs;
            } else {
                $tags[ $tag ] = array_merge( $tags[ $tag ], $attrs );
            }
        }
        return $tags;
    }
} 