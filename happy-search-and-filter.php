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
        }

        /**
         * Enqueue front-end assets.
         */
        public function enqueue_assets() {
            $style_path = HSF_PLUGIN_DIR . 'assets/css/search-filter.css';
            $script_path = HSF_PLUGIN_DIR . 'assets/js/search-filter.js';

            wp_enqueue_style( 'hsf-style', HSF_PLUGIN_URL . 'assets/css/search-filter.css', array(), filemtime( $style_path ) );

            wp_enqueue_script( 'hsf-script', HSF_PLUGIN_URL . 'assets/js/search-filter.js', array( 'jquery' ), filemtime( $script_path ), true );

            // Saved filters script
            $saved_script_path = HSF_PLUGIN_DIR . 'assets/js/hsf-saved-filters.js';
            wp_enqueue_script( 'hsf-saved-filters', HSF_PLUGIN_URL . 'assets/js/hsf-saved-filters.js', array( 'jquery' ), filemtime( $saved_script_path ), true );

            wp_localize_script( 'hsf-saved-filters', 'hsfSaved', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'hsf_saved_filter_nonce' ),
                'i18n'    => array(
                    'promptName' => __( 'Enter a name for this filter', 'happy-search-and-filter' ),
                ),
            ) );

            // Advanced filter script and styles
            $advanced_script_path = HSF_PLUGIN_DIR . 'assets/js/advanced-filter.js';
            $advanced_style_path = HSF_PLUGIN_DIR . 'assets/css/advanced-filter.css';
            
            wp_enqueue_style( 'hsf-advanced-filter', HSF_PLUGIN_URL . 'assets/css/advanced-filter.css', array(), filemtime( $advanced_style_path ) );
            wp_enqueue_script( 'hsf-advanced-filter', HSF_PLUGIN_URL . 'assets/js/advanced-filter.js', array( 'jquery', 'jquery-ui-datepicker' ), filemtime( $advanced_script_path ), true );

            wp_localize_script( 'hsf-advanced-filter', 'hsfAdvancedFilter', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'hsf_advanced_filter_nonce' ),
                'i18n'    => array(
                    'showFilters' => __( 'Show Filters', 'happy-search-and-filter' ),
                    'hideFilters' => __( 'Hide Filters', 'happy-search-and-filter' ),
                    'useMyLocation' => __( 'Use my location', 'happy-search-and-filter' ),
                    'noSavedFilters' => __( 'No saved filters yet.', 'happy-search-and-filter' ),
                    'saveFilterPrompt' => __( 'Enter a name for this filter:', 'happy-search-and-filter' ),
                    'deleteFilterConfirm' => __( 'Are you sure you want to delete this saved filter?', 'happy-search-and-filter' ),
                ),
            ) );

            // Enqueue jQuery UI for datepicker
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
                    'enableSavedFilters'  => array( 'type' => 'boolean','default' => false ),
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
                    'enableSavedFilters' => false,
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
