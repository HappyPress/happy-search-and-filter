<?php
// Data preparation (mimic HBL logic, but use HSF text domain and function names)
$attributes = isset( $attributes ) ? $attributes : array();
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
    'className' => '',
    'paginationType' => 'load_more', // Added for pagination type
    'enableInfiniteScroll' => false // Added for infinite scroll
);
$attributes = array_merge( $defaults, $attributes );

// Get filter options (replace with HSF equivalents or fallback)
$categories = array();
$company_types = array();
$locations = array();
$services = array();
$tags = array();

try {
    // Get categories
    if ($attributes['showCategoryFilter']) {
        $categories = get_terms(array('taxonomy' => 'business_category', 'hide_empty' => true));
        if (is_wp_error($categories)) $categories = array();
    }
    
    // Get company types
    if ($attributes['showCompanyTypeFilter'] && function_exists('hsf_get_meta_values')) {
        $company_types = hsf_get_meta_values('company_type', 'business_listing');
        if (!is_array($company_types)) $company_types = array();
    }
    
    // Get locations
    if ($attributes['showLocationFilter'] && function_exists('hsf_get_meta_values')) {
        $locations = hsf_get_meta_values('location', 'business_listing');
        if (!is_array($locations)) $locations = array();
    }
    
    // Get services
    if ($attributes['showServiceFilter'] && function_exists('hsf_get_meta_values')) {
        $services = hsf_get_meta_values('services', 'business_listing', true);
        if (!is_array($services)) $services = array();
    }
    
    // Get tags
    if ($attributes['showTagsFilter']) {
        $tags = get_terms(array('taxonomy' => 'business_tag', 'hide_empty' => true));
        if (is_wp_error($tags)) $tags = array();
    }
} catch (Exception $e) {
    // Log error but continue with empty arrays
    error_log('HSF Template Error: ' . $e->getMessage());
}

$price_ranges = array(
    'low' => __('Low', 'happy-search-and-filter'),
    'medium' => __('Medium', 'happy-search-and-filter'),
    'high' => __('High', 'happy-search-and-filter'),
    'premium' => __('Premium', 'happy-search-and-filter')
);

$distance_options = array_filter(array_map('trim', explode(',', $attributes['maxDistanceOptions'])), 'is_numeric');

// Current values (no $_GET, just empty for now)
$current_search = $current_category = $current_location = $current_company_type = $current_price_range = '';
$current_rating = $current_verified = $current_orderby = $current_order = '';
$current_date_from = $current_date_to = '';
$current_services = $current_tags = array();
$current_distance = 0;
$current_distance_unit = $attributes['defaultDistanceUnit'];
$current_latitude = $current_longitude = 0;
$current_open_now = false;

$filter_class = 'hbl-advanced-filter hsf-filter-wrapper';
if (!empty($attributes['className'])) $filter_class .= ' ' . $attributes['className'];
$filter_class .= ' hbl-filter-layout-' . $attributes['layout'];
$filter_class .= ' hbl-filter-style-' . $attributes['filterStyle'];

$data_attrs = '';
$data_attrs .= ' data-auto-submit="' . ($attributes['enableAutoSubmit'] ? 'true' : 'false') . '"';
$data_attrs .= ' data-saved-filters="' . ($attributes['enableSavedFilters'] ? 'true' : 'false') . '"';
$data_attrs .= ' data-pagination-type="' . (isset($attributes['paginationType']) ? esc_attr($attributes['paginationType']) : 'load_more') . '"';
$data_attrs .= ' data-infinite-scroll="' . (isset($attributes['enableInfiniteScroll']) ? ($attributes['enableInfiniteScroll'] ? 'true' : 'false') : 'false') . '"';
?>
<div class="<?php echo esc_attr($filter_class); ?>"<?php echo $data_attrs; ?>>
    <?php if (!empty($attributes['title'])) : ?>
        <h3 class="hbl-filter-title"><?php echo esc_html($attributes['title']); ?></h3>
    <?php endif; ?>
    
    <form id="hsf-search-form" class="hbl-advanced-filter-form" method="post" action="#">
        <div class="hbl-filter-fields">
            <?php if ($attributes['showKeywordSearch']) : ?>
                <div class="hbl-filter-field hbl-filter-search">
                    <label for="hsf-filter-search"><?php _e('SEARCH', 'happy-search-and-filter'); ?></label>
                    <input type="text" name="search" id="hsf-filter-search" placeholder="<?php esc_attr_e('Search businesses...', 'happy-search-and-filter'); ?>" value="<?php echo esc_attr($current_search); ?>">
                </div>
            <?php endif; ?>
            
            <?php if ($attributes['showLocationFilter'] && !empty($locations)) : ?>
                <div class="hbl-filter-field hbl-filter-location">
                    <label for="hsf-filter-location"><?php _e('LOCATION', 'happy-search-and-filter'); ?></label>
                    <select name="location" id="hsf-filter-location">
                        <option value=""><?php _e('All Locations', 'happy-search-and-filter'); ?></option>
                        <?php foreach ($locations as $location) : ?>
                            <option value="<?php echo esc_attr($location); ?>" <?php selected($current_location, $location); ?>>
                                <?php echo esc_html($location); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if ($attributes['showCompanyTypeFilter'] && !empty($company_types)) : ?>
                <div class="hbl-filter-field hbl-filter-company-type">
                    <label for="hsf-filter-company-type"><?php _e('COMPANY TYPE', 'happy-search-and-filter'); ?></label>
                    <select name="company_type" id="hsf-filter-company-type">
                        <option value=""><?php _e('All Types', 'happy-search-and-filter'); ?></option>
                        <?php foreach ($company_types as $type) : ?>
                            <option value="<?php echo esc_attr($type); ?>" <?php selected($current_company_type, $type); ?>>
                                <?php echo esc_html($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if ($attributes['showCategoryFilter'] && !empty($categories)) : ?>
                <div class="hbl-filter-field hbl-filter-category">
                    <label for="hsf-filter-category"><?php _e('CATEGORY', 'happy-search-and-filter'); ?></label>
                    <select name="category" id="hsf-filter-category">
                        <option value=""><?php _e('All Categories', 'happy-search-and-filter'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($current_category, $category->slug); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if ($attributes['showSorting']) : ?>
                <div class="hbl-filter-field hbl-filter-sorting">
                    <label><?php _e('SORT BY', 'happy-search-and-filter'); ?></label>
                    <div class="hbl-sorting-selects">
                        <select name="orderby" id="hsf-filter-orderby">
                            <option value="date" <?php selected($current_orderby, 'date'); ?>><?php _e('Date', 'happy-search-and-filter'); ?></option>
                            <option value="title" <?php selected($current_orderby, 'title'); ?>><?php _e('Name', 'happy-search-and-filter'); ?></option>
                            <option value="rating" <?php selected($current_orderby, 'rating'); ?>><?php _e('Rating', 'happy-search-and-filter'); ?></option>
                            <option value="popularity" <?php selected($current_orderby, 'popularity'); ?>><?php _e('Popularity', 'happy-search-and-filter'); ?></option>
                        </select>
                        <select name="order" id="hsf-filter-order">
                            <option value="DESC" <?php selected($current_order, 'DESC'); ?>><?php _e('Descending', 'happy-search-and-filter'); ?></option>
                            <option value="ASC" <?php selected($current_order, 'ASC'); ?>><?php _e('Ascending', 'happy-search-and-filter'); ?></option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($attributes['showRatingFilter']) : ?>
                <div class="hbl-filter-field hbl-filter-rating">
                    <label for="hsf-filter-rating"><?php _e('RATING', 'happy-search-and-filter'); ?></label>
                    <select name="rating_min" id="hsf-filter-rating">
                        <option value=""><?php _e('Any Rating', 'happy-search-and-filter'); ?></option>
                        <option value="5" <?php selected($current_rating, 5); ?>><?php _e('5 Stars', 'happy-search-and-filter'); ?></option>
                        <option value="4" <?php selected($current_rating, 4); ?>><?php _e('4+ Stars', 'happy-search-and-filter'); ?></option>
                        <option value="3" <?php selected($current_rating, 3); ?>><?php _e('3+ Stars', 'happy-search-and-filter'); ?></option>
                        <option value="2" <?php selected($current_rating, 2); ?>><?php _e('2+ Stars', 'happy-search-and-filter'); ?></option>
                        <option value="1" <?php selected($current_rating, 1); ?>><?php _e('1+ Stars', 'happy-search-and-filter'); ?></option>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if ($attributes['showPriceRangeFilter']) : ?>
                <div class="hbl-filter-field hbl-filter-price-range">
                    <label for="hsf-filter-price-range"><?php _e('PRICE RANGE', 'happy-search-and-filter'); ?></label>
                    <select name="price_range" id="hsf-filter-price-range">
                        <option value=""><?php _e('Any Price', 'happy-search-and-filter'); ?></option>
                        <?php foreach ($price_ranges as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($current_price_range, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if ($attributes['showVerifiedFilter']) : ?>
                <div class="hbl-filter-field hbl-filter-verified">
                    <label for="hsf-filter-verified" class="hbl-checkbox-label">
                        <input type="checkbox" name="verified" id="hsf-filter-verified" value="1" <?php checked($current_verified); ?>>
                        <?php _e('Verified Only', 'happy-search-and-filter'); ?>
                    </label>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="hbl-filter-actions">
            <div class="hbl-filter-actions-left">
                <button type="submit" class="hbl-submit-button">
                    <svg class="hbl-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <?php _e('Search', 'happy-search-and-filter'); ?>
                </button>
                <button type="button" class="hbl-reset-button">
                    <svg class="hbl-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                        <path d="M21 3v5h-5"></path>
                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path>
                        <path d="M3 21v-5h5"></path>
                    </svg>
                    <?php _e('Reset', 'happy-search-and-filter'); ?>
                </button>
            </div>
            
            <?php if ($attributes['enableSavedFilters']) : ?>
            <div class="hbl-filter-actions-right">
                <div class="hbl-saved-filters-controls">
                    <button type="button" class="hbl-save-filter-button" id="hbl-save-filter-btn">
                        <svg class="hbl-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17,21 17,13 7,13 7,21"></polyline>
                            <polyline points="7,3 7,8 15,8"></polyline>
                        </svg>
                        <?php _e('Save Filter', 'happy-search-and-filter'); ?>
                    </button>
                    
                    <div class="hbl-saved-filters-dropdown" id="hbl-saved-filters-dropdown" style="display: none;">
                        <div class="hbl-saved-filters-header">
                            <h4><?php _e('Saved Filters', 'happy-search-and-filter'); ?></h4>
                            <button type="button" class="hbl-close-dropdown" id="hbl-close-dropdown">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 6L6 18M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="hbl-saved-filters-list" id="hbl-saved-filters-list">
                            <!-- Saved filters will be loaded here via JavaScript -->
                        </div>
                        <div class="hbl-saved-filters-empty" id="hbl-saved-filters-empty" style="display: none;">
                            <p><?php _e('No saved filters yet.', 'happy-search-and-filter'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <input type="hidden" name="results_per_page" value="<?php echo esc_attr($attributes['resultsPerPage']); ?>">
    </form>
    
    <div id="hsf-search-results" class="hbl-search-results"></div>
</div>
