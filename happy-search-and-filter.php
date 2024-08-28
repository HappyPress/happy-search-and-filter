<?php
/*
Plugin Name: Happy Search and Filter
Description: A custom plugin for advanced search and filter functionality with Gutenberg blocks, shortcode support, and various configuration options.
Version: 1.0
Author: HappyPress, patilswapnilv
*/

// Enqueue necessary scripts and styles
function hsf_enqueue_assets() {
    wp_enqueue_style('hsf-style', plugin_dir_url(__FILE__) . 'assets/css/search-filter.css');
    wp_enqueue_script('hsf-script', plugin_dir_url(__FILE__) . 'assets/js/search-filter.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'hsf_enqueue_assets');

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/search-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/search-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handler.php';

// Register Gutenberg blocks
function hsf_register_blocks() {
    wp_register_script('hsf-block-editor', plugin_dir_url(__FILE__) . 'assets/js/search-filter-editor.js', array('wp-blocks', 'wp-element', 'wp-editor'), null, true);
    register_block_type('hsf/search-filter', array(
        'editor_script' => 'hsf-block-editor',
        'render_callback' => 'hsf_render_search_filter_block',
    ));
}
add_action('init', 'hsf_register_blocks');

// Render callback for the search filter block
function hsf_render_search_filter_block($attributes) {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/search-filter-template.php';
    return ob_get_clean();
}
