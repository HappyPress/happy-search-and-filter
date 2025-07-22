<?php
/**
 * Plugin Name: Happy Search and Filter
 * Description: Advanced search and filter functionality with Gutenberg blocks, shortcode support, and various configuration options.
 * Version: 1.1.0
 * Author: HappyPress, patilswapnilv
 * Text Domain: happy-search-and-filter
 * Domain Path: /languages
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------------------------------------
 *  Define constants
 * -------------------------------------------------------------------------*/

if ( ! defined( 'HSF_VERSION' ) ) {
    define( 'HSF_VERSION', '1.1.0' );
}

if ( ! defined( 'HSF_PLUGIN_FILE' ) ) {
    define( 'HSF_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'HSF_PLUGIN_DIR' ) ) {
    define( 'HSF_PLUGIN_DIR', plugin_dir_path( HSF_PLUGIN_FILE ) );
}

if ( ! defined( 'HSF_PLUGIN_URL' ) ) {
    define( 'HSF_PLUGIN_URL', plugin_dir_url( HSF_PLUGIN_FILE ) );
}

/* -------------------------------------------------------------------------
 *  Main plugin class (singleton)
 * -------------------------------------------------------------------------*/

if ( ! class_exists( 'Happy_Search_Filter' ) ) {

    final class Happy_Search_Filter {

        /**
         * Singleton instance.
         *
         * @var Happy_Search_Filter|null
         */
        private static $instance = null;

        /**
         * Get plugin instance.
         */
        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Constructor – set up hooks.
         */
        private function __construct() {

            // Load text-domain for translations.
            add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

            // Register public & editor assets.
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

            // Register Gutenberg blocks after init.
            add_action( 'init', array( $this, 'register_blocks' ) );

            // Include plugin modules.
            $this->include_files();
        }

        /**
         * Load plugin translation files.
         */
        public function load_textdomain() {
            load_plugin_textdomain( 'happy-search-and-filter', false, dirname( plugin_basename( HSF_PLUGIN_FILE ) ) . '/languages' );
        }

        /**
         * Include modular PHP files.
         */
        private function include_files() {
            require_once HSF_PLUGIN_DIR . 'includes/helpers.php';
            require_once HSF_PLUGIN_DIR . 'includes/settings.php';
            require_once HSF_PLUGIN_DIR . 'includes/search-shortcode.php';
            require_once HSF_PLUGIN_DIR . 'includes/ajax-handler.php';
            require_once HSF_PLUGIN_DIR . 'includes/saved-filters.php';
            require_once HSF_PLUGIN_DIR . 'includes/caching.php';
            require_once HSF_PLUGIN_DIR . 'includes/class-hsf-assets.php';
            require_once HSF_PLUGIN_DIR . 'includes/class-hsf-performance-admin.php';
            require_once HSF_PLUGIN_DIR . 'includes/class-hsf-critical-css.php';
            
            // Include advanced search block for page-based directory
            if (function_exists('register_block_type')) {
                require_once HSF_PLUGIN_DIR . 'includes/advanced-search-block.php';
            }
        }

        /**
         * Enqueue front-end assets.
         * Note: Asset optimization is now handled by HSF_Assets class
         */
        public function enqueue_assets() {
            // Asset optimization and minification is handled by HSF_Assets class
            // This method is kept for backward compatibility and any additional assets
            
            // Enqueue jQuery UI for datepicker (if needed)
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
        }

        /**
         * Register Gutenberg blocks.
         */
        public function register_blocks() {
            // Editor script & style.
            wp_register_script(
                'hsf-block-editor',
                HSF_PLUGIN_URL . 'assets/js/search-filter-editor.js',
                array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
                filemtime( HSF_PLUGIN_DIR . 'assets/js/search-filter-editor.js' ),
                true
            );

            wp_register_style(
                'hsf-block-editor-style',
                HSF_PLUGIN_URL . 'assets/css/search-filter-editor.css',
                array( 'wp-edit-blocks' ),
                filemtime( HSF_PLUGIN_DIR . 'assets/css/search-filter-editor.css' )
            );

            // Register block type.
            register_block_type( 'happy-search-and-filter/advanced-search', array(
                'editor_script'   => 'hsf-block-editor',
                'editor_style'    => 'hsf-block-editor-style',
                'render_callback' => array( $this, 'render_search_filter_block' ),
                'attributes'      => array(
                    'title'               => array( 'type' => 'string',  'default' => __( 'Find Businesses', 'happy-search-and-filter' ) ),
                    'layout'              => array( 'type' => 'string',  'default' => 'horizontal' ),
                    'showKeywordSearch'   => array( 'type' => 'boolean','default' => true ),
                    'showLocationFilter'  => array( 'type' => 'boolean','default' => true ),
                    'showCategoryFilter'  => array( 'type' => 'boolean','default' => true ),
                    'showCompanyTypeFilter'=>array( 'type' => 'boolean','default' => true ),
                    'showRatingFilter'    => array( 'type' => 'boolean','default' => false ),
                    'showPriceRangeFilter'=> array( 'type' => 'boolean','default' => false ),
                    'showVerifiedFilter'  => array( 'type' => 'boolean','default' => false ),
                    'showSorting'         => array( 'type' => 'boolean','default' => true ),
                    'resultsPerPage'      => array( 'type' => 'number', 'default' => 10 ),
                    'showDateRangeFilter' => array( 'type' => 'boolean','default' => false ),
                    'showServiceFilter'   => array( 'type' => 'boolean','default' => false ),
                    'showDistanceFilter'  => array( 'type' => 'boolean','default' => false ),
                    'showTagsFilter'      => array( 'type' => 'boolean','default' => false ),
                    'showOpenNowFilter'   => array( 'type' => 'boolean','default' => false ),
                    'maxDistanceOptions'  => array( 'type' => 'string', 'default' => '5,10,25,50,100' ),
                    'defaultDistanceUnit' => array( 'type' => 'string', 'default' => 'km' ),
                    'enableAutoSubmit'    => array( 'type' => 'boolean','default' => true ),
                    'enableSavedFilters'  => array( 'type' => 'boolean','default' => true ),
                    'filterStyle'         => array( 'type' => 'string', 'default' => 'standard' ),
                    'showFilterToggle'    => array( 'type' => 'boolean','default' => false ),
                    'className'           => array( 'type' => 'string' ),
                ),
            ) );
        }

        /**
         * Server-side render callback for the block.
         */
        public function render_search_filter_block( $attributes ) {
            // Add error handling and debugging
            try {
                // Ensure attributes is an array
                if (!is_array($attributes)) {
                    $attributes = array();
                }
                
                // Set default attributes
                $defaults = array(
                    'title' => __( 'Find Businesses', 'happy-search-and-filter' ),
                    'layout' => 'horizontal',
                    'showKeywordSearch' => true,
                    'showLocationFilter' => true,
                    'showCategoryFilter' => true,
                    'showCompanyTypeFilter' => true,
                    'showRatingFilter' => false,
                    'showPriceRangeFilter' => false,
                    'showVerifiedFilter' => false,
                    'showSorting' => true,
                    'resultsPerPage' => 10,
                    'showDateRangeFilter' => false,
                    'showServiceFilter' => false,
                    'showDistanceFilter' => false,
                    'showTagsFilter' => false,
                    'showOpenNowFilter' => false,
                    'maxDistanceOptions' => '5,10,25,50,100',
                    'defaultDistanceUnit' => 'km',
                    'enableAutoSubmit' => true,
                    'enableSavedFilters' => true,
                    'filterStyle' => 'standard',
                    'showFilterToggle' => false,
                    'className' => ''
                );
                
                $attributes = array_merge($defaults, $attributes);
                
                ob_start();
                include HSF_PLUGIN_DIR . 'templates/search-filter-template.php';
                return ob_get_clean();
                
            } catch (Exception $e) {
                // Log error and return a simple fallback
                error_log('HSF Block Render Error: ' . $e->getMessage());
                return '<div class="hsf-error">Advanced Search Filter - Configuration Error</div>';
            }
        }
    }
}

// Bootstrap the plugin.
Happy_Search_Filter::get_instance();

/* -------------------------------------------------------------------------
 *  Back-compat stubs (optional)
 * -------------------------------------------------------------------------*/

// Maintain existing procedural render callback used by the shortcode.
if ( ! function_exists( 'hsf_render_search_filter_block' ) ) {
    function hsf_render_search_filter_block( $attributes ) {
        return Happy_Search_Filter::get_instance()->render_search_filter_block( $attributes );
    }
}
