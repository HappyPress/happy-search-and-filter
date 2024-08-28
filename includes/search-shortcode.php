<?php
// Shortcode for the search filter
function hsf_search_filter_shortcode($atts) {
    ob_start();
    include plugin_dir_path(__FILE__) . '../templates/search-filter-template.php';
    return ob_get_clean();
}
add_shortcode('hsf_search_filter', 'hsf_search_filter_shortcode');
?>
