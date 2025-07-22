<?php
// Shortcode for the search filter
function hsf_search_filter_shortcode($atts) {
    ob_start();
    include plugin_dir_path(__FILE__) . '../templates/search-filter-template.php';
    return ob_get_clean();
}
add_shortcode('hsf_search_filter', 'hsf_search_filter_shortcode');

// Advanced search shortcode for compatibility
function hsf_advanced_search_shortcode($atts) {
    // Convert shortcode attributes to block attributes format
    $block_attributes = array();
    
    if (isset($atts['title'])) {
        $block_attributes['title'] = $atts['title'];
    }
    if (isset($atts['showKeywordSearch'])) {
        $block_attributes['showKeywordSearch'] = filter_var($atts['showKeywordSearch'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showLocationFilter'])) {
        $block_attributes['showLocationFilter'] = filter_var($atts['showLocationFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showCategoryFilter'])) {
        $block_attributes['showCategoryFilter'] = filter_var($atts['showCategoryFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showCompanyTypeFilter'])) {
        $block_attributes['showCompanyTypeFilter'] = filter_var($atts['showCompanyTypeFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showSorting'])) {
        $block_attributes['showSorting'] = filter_var($atts['showSorting'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showRatingFilter'])) {
        $block_attributes['showRatingFilter'] = filter_var($atts['showRatingFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showPriceRangeFilter'])) {
        $block_attributes['showPriceRangeFilter'] = filter_var($atts['showPriceRangeFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showVerifiedFilter'])) {
        $block_attributes['showVerifiedFilter'] = filter_var($atts['showVerifiedFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showDateRangeFilter'])) {
        $block_attributes['showDateRangeFilter'] = filter_var($atts['showDateRangeFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showServiceFilter'])) {
        $block_attributes['showServiceFilter'] = filter_var($atts['showServiceFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showDistanceFilter'])) {
        $block_attributes['showDistanceFilter'] = filter_var($atts['showDistanceFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showTagsFilter'])) {
        $block_attributes['showTagsFilter'] = filter_var($atts['showTagsFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['showOpenNowFilter'])) {
        $block_attributes['showOpenNowFilter'] = filter_var($atts['showOpenNowFilter'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['enableSavedFilters'])) {
        $block_attributes['enableSavedFilters'] = filter_var($atts['enableSavedFilters'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($atts['resultsPerPage'])) {
        $block_attributes['resultsPerPage'] = intval($atts['resultsPerPage']);
    }
    if (isset($atts['layout'])) {
        $block_attributes['layout'] = $atts['layout'];
    }
    if (isset($atts['filterStyle'])) {
        $block_attributes['filterStyle'] = $atts['filterStyle'];
    }
    
    // Use the same render function as the block
    if (function_exists('hsf_render_search_filter_block')) {
        return hsf_render_search_filter_block($block_attributes);
    } else {
        // Fallback to direct template inclusion
        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/search-filter-template.php';
        return ob_get_clean();
    }
}
add_shortcode('hsf_advanced_search', 'hsf_advanced_search_shortcode');
?>
