<?php
/**
 * HSF Performance Admin Class
 * 
 * Provides admin interface for performance optimization settings and statistics
 * 
 * @package HappySearchAndFilter
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSF_Performance_Admin {
    
    /**
     * Initialize the performance admin
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('wp_ajax_hsf_clear_asset_cache', array(__CLASS__, 'ajax_clear_asset_cache'));
        add_action('wp_ajax_hsf_generate_optimized_assets', array(__CLASS__, 'ajax_generate_optimized_assets'));
        add_action('wp_ajax_hsf_get_performance_stats', array(__CLASS__, 'ajax_get_performance_stats'));
    }
    
    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __('HSF Performance', 'happy-search-and-filter'),
            __('HSF Performance', 'happy-search-and-filter'),
            'manage_options',
            'hsf-performance',
            array(__CLASS__, 'render_admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('hsf_performance_options', 'hsf_performance_settings');
        
        add_settings_section(
            'hsf_performance_general',
            __('Performance Settings', 'happy-search-and-filter'),
            array(__CLASS__, 'render_general_section'),
            'hsf_performance_options'
        );
        
        add_settings_field(
            'enable_asset_minification',
            __('Enable Asset Minification', 'happy-search-and-filter'),
            array(__CLASS__, 'render_checkbox_field'),
            'hsf_performance_options',
            'hsf_performance_general',
            array(
                'label_for' => 'enable_asset_minification',
                'description' => __('Minify CSS and JavaScript files for faster loading', 'happy-search-and-filter')
            )
        );
        
        add_settings_field(
            'enable_asset_caching',
            __('Enable Asset Caching', 'happy-search-and-filter'),
            array(__CLASS__, 'render_checkbox_field'),
            'hsf_performance_options',
            'hsf_performance_general',
            array(
                'label_for' => 'enable_asset_caching',
                'description' => __('Cache optimized assets for better performance', 'happy-search-and-filter')
            )
        );
        
        add_settings_field(
            'enable_critical_css',
            __('Enable Critical CSS', 'happy-search-and-filter'),
            array(__CLASS__, 'render_checkbox_field'),
            'hsf_performance_options',
            'hsf_performance_general',
            array(
                'label_for' => 'enable_critical_css',
                'description' => __('Inline critical CSS for faster initial render', 'happy-search-and-filter')
            )
        );
        
        add_settings_field(
            'enable_js_defer',
            __('Defer JavaScript Loading', 'happy-search-and-filter'),
            array(__CLASS__, 'render_checkbox_field'),
            'hsf_performance_options',
            'hsf_performance_general',
            array(
                'label_for' => 'enable_js_defer',
                'description' => __('Load JavaScript after page content for better performance', 'happy-search-and-filter')
            )
        );
    }
    
    /**
     * Render admin page
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'happy-search-and-filter'));
        }
        
        $settings = get_option('hsf_performance_settings', array());
        $stats = HSF_Assets::get_optimization_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('HSF Performance Optimization', 'happy-search-and-filter'); ?></h1>
            
            <div class="hsf-performance-dashboard">
                <!-- Performance Stats -->
                <div class="hsf-stats-section">
                    <h2><?php _e('Performance Statistics', 'happy-search-and-filter'); ?></h2>
                    <div class="hsf-stats-grid">
                        <div class="hsf-stat-card">
                            <h3><?php _e('CSS Optimization', 'happy-search-and-filter'); ?></h3>
                            <div class="hsf-stat-content">
                                <p><strong><?php _e('Original Size:', 'happy-search-and-filter'); ?></strong> <?php echo size_format($stats['css_original_size']); ?></p>
                                <p><strong><?php _e('Optimized Size:', 'happy-search-and-filter'); ?></strong> <?php echo size_format($stats['css_optimized_size']); ?></p>
                                <p><strong><?php _e('Savings:', 'happy-search-and-filter'); ?></strong> 
                                    <span class="hsf-savings <?php echo $stats['css_original_size'] > 0 ? 'positive' : 'neutral'; ?>">
                                        <?php 
                                        if ($stats['css_original_size'] > 0) {
                                            $css_savings = round((($stats['css_original_size'] - $stats['css_optimized_size']) / $stats['css_original_size']) * 100, 2);
                                            echo $css_savings . '%';
                                        } else {
                                            echo '0%';
                                        }
                                        ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="hsf-stat-card">
                            <h3><?php _e('JavaScript Optimization', 'happy-search-and-filter'); ?></h3>
                            <div class="hsf-stat-content">
                                <p><strong><?php _e('Original Size:', 'happy-search-and-filter'); ?></strong> <?php echo size_format($stats['js_original_size']); ?></p>
                                <p><strong><?php _e('Optimized Size:', 'happy-search-and-filter'); ?></strong> <?php echo size_format($stats['js_optimized_size']); ?></p>
                                <p><strong><?php _e('Savings:', 'happy-search-and-filter'); ?></strong> 
                                    <span class="hsf-savings <?php echo $stats['js_original_size'] > 0 ? 'positive' : 'neutral'; ?>">
                                        <?php 
                                        if ($stats['js_original_size'] > 0) {
                                            $js_savings = round((($stats['js_original_size'] - $stats['js_optimized_size']) / $stats['js_original_size']) * 100, 2);
                                            echo $js_savings . '%';
                                        } else {
                                            echo '0%';
                                        }
                                        ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="hsf-stat-card">
                            <h3><?php _e('Total Savings', 'happy-search-and-filter'); ?></h3>
                            <div class="hsf-stat-content">
                                <p><strong><?php _e('Overall Savings:', 'happy-search-and-filter'); ?></strong> 
                                    <span class="hsf-savings <?php echo $stats['savings_percentage'] > 0 ? 'positive' : 'neutral'; ?>">
                                        <?php echo $stats['savings_percentage']; ?>%
                                    </span>
                                </p>
                                <p><strong><?php _e('Total Optimized:', 'happy-search-and-filter'); ?></strong> 
                                    <?php echo size_format($stats['css_optimized_size'] + $stats['js_optimized_size']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="hsf-actions-section">
                    <h2><?php _e('Quick Actions', 'happy-search-and-filter'); ?></h2>
                    <div class="hsf-actions-grid">
                        <button type="button" class="button button-primary" id="hsf-generate-assets">
                            <?php _e('Generate Optimized Assets', 'happy-search-and-filter'); ?>
                        </button>
                        <button type="button" class="button button-secondary" id="hsf-clear-cache">
                            <?php _e('Clear Asset Cache', 'happy-search-and-filter'); ?>
                        </button>
                        <button type="button" class="button button-secondary" id="hsf-refresh-stats">
                            <?php _e('Refresh Statistics', 'happy-search-and-filter'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Settings Form -->
                <div class="hsf-settings-section">
                    <h2><?php _e('Performance Settings', 'happy-search-and-filter'); ?></h2>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('hsf_performance_options');
                        do_settings_sections('hsf_performance_options');
                        submit_button(__('Save Settings', 'happy-search-and-filter'));
                        ?>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
        .hsf-performance-dashboard {
            margin-top: 20px;
        }
        
        .hsf-stats-section,
        .hsf-actions-section,
        .hsf-settings-section {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        
        .hsf-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .hsf-stat-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        
        .hsf-stat-card h3 {
            margin-top: 0;
            color: #0073aa;
        }
        
        .hsf-savings.positive {
            color: #46b450;
            font-weight: bold;
        }
        
        .hsf-savings.neutral {
            color: #666;
        }
        
        .hsf-actions-grid {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .hsf-loading {
            opacity: 0.5;
            pointer-events: none;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Generate optimized assets
            $('#hsf-generate-assets').on('click', function() {
                var $button = $(this);
                $button.addClass('hsf-loading').text('<?php _e('Generating...', 'happy-search-and-filter'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hsf_generate_optimized_assets',
                        nonce: '<?php echo wp_create_nonce('hsf_performance_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Optimized assets generated successfully!', 'happy-search-and-filter'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Error generating optimized assets.', 'happy-search-and-filter'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error generating optimized assets.', 'happy-search-and-filter'); ?>');
                    },
                    complete: function() {
                        $button.removeClass('hsf-loading').text('<?php _e('Generate Optimized Assets', 'happy-search-and-filter'); ?>');
                    }
                });
            });
            
            // Clear asset cache
            $('#hsf-clear-cache').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to clear the asset cache?', 'happy-search-and-filter'); ?>')) {
                    var $button = $(this);
                    $button.addClass('hsf-loading').text('<?php _e('Clearing...', 'happy-search-and-filter'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'hsf_clear_asset_cache',
                            nonce: '<?php echo wp_create_nonce('hsf_performance_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php _e('Asset cache cleared successfully!', 'happy-search-and-filter'); ?>');
                                location.reload();
                            } else {
                                alert('<?php _e('Error clearing asset cache.', 'happy-search-and-filter'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php _e('Error clearing asset cache.', 'happy-search-and-filter'); ?>');
                        },
                        complete: function() {
                            $button.removeClass('hsf-loading').text('<?php _e('Clear Asset Cache', 'happy-search-and-filter'); ?>');
                        }
                    });
                }
            });
            
            // Refresh statistics
            $('#hsf-refresh-stats').on('click', function() {
                var $button = $(this);
                $button.addClass('hsf-loading').text('<?php _e('Refreshing...', 'happy-search-and-filter'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hsf_get_performance_stats',
                        nonce: '<?php echo wp_create_nonce('hsf_performance_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php _e('Error refreshing statistics.', 'happy-search-and-filter'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error refreshing statistics.', 'happy-search-and-filter'); ?>');
                    },
                    complete: function() {
                        $button.removeClass('hsf-loading').text('<?php _e('Refresh Statistics', 'happy-search-and-filter'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render general section
     */
    public static function render_general_section() {
        echo '<p>' . __('Configure performance optimization settings for the HSF plugin.', 'happy-search-and-filter') . '</p>';
    }
    
    /**
     * Render checkbox field
     */
    public static function render_checkbox_field($args) {
        $settings = get_option('hsf_performance_settings', array());
        $field_id = $args['label_for'];
        $description = $args['description'];
        $value = isset($settings[$field_id]) ? $settings[$field_id] : false;
        
        echo '<label>';
        echo '<input type="checkbox" id="' . esc_attr($field_id) . '" name="hsf_performance_settings[' . esc_attr($field_id) . ']" value="1" ' . checked($value, true, false) . ' />';
        echo ' ' . esc_html($description);
        echo '</label>';
    }
    
    /**
     * AJAX handler for clearing asset cache
     */
    public static function ajax_clear_asset_cache() {
        check_ajax_referer('hsf_performance_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        HSF_Assets::clear_asset_cache();
        wp_send_json_success('Asset cache cleared successfully');
    }
    
    /**
     * AJAX handler for generating optimized assets
     */
    public static function ajax_generate_optimized_assets() {
        check_ajax_referer('hsf_performance_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Force regeneration of optimized assets
        $css_source = plugin_dir_path(__FILE__) . '../assets/css/advanced-filter.css';
        $js_source = plugin_dir_path(__FILE__) . '../assets/js/advanced-filter.js';
        $css_cache = WP_CONTENT_DIR . '/cache/hsf-assets/advanced-filter.min.css';
        $js_cache = WP_CONTENT_DIR . '/cache/hsf-assets/advanced-filter.min.js';
        
        // Clear existing cache
        HSF_Assets::clear_asset_cache();
        
        // Generate new optimized assets
        $css_success = file_exists($css_source) ? HSF_Assets::minify_css($css_source, $css_cache) : false;
        $js_success = file_exists($js_source) ? HSF_Assets::minify_js($js_source, $js_cache) : false;
        
        if ($css_success || $js_success) {
            wp_send_json_success('Optimized assets generated successfully');
        } else {
            wp_send_json_error('Error generating optimized assets');
        }
    }
    
    /**
     * AJAX handler for getting performance stats
     */
    public static function ajax_get_performance_stats() {
        check_ajax_referer('hsf_performance_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $stats = HSF_Assets::get_optimization_stats();
        wp_send_json_success($stats);
    }
}

// Initialize the performance admin
HSF_Performance_Admin::init(); 