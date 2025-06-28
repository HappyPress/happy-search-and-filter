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
    'className' => ''
);
$attributes = array_merge( $defaults, $attributes );

// Get filter options (replace with HSF equivalents or fallback)
$categories = get_terms(array('taxonomy' => 'business_category', 'hide_empty' => true));
if (is_wp_error($categories)) $categories = array();
$company_types = function_exists('hbl_get_meta_values') ? hbl_get_meta_values('company_type', 'business_listing') : array();
$locations = function_exists('hbl_get_meta_values') ? hbl_get_meta_values('location', 'business_listing') : array();
$price_ranges = array(
    'low' => __('Low', 'happy-search-and-filter'),
    'medium' => __('Medium', 'happy-search-and-filter'),
    'high' => __('High', 'happy-search-and-filter'),
    'premium' => __('Premium', 'happy-search-and-filter')
);
$services = $attributes['showServiceFilter'] && function_exists('hbl_get_meta_values') ? hbl_get_meta_values('services', 'business_listing', true) : array();
$tags = get_terms(array('taxonomy' => 'business_tag', 'hide_empty' => true));
if (is_wp_error($tags)) $tags = array();
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
?>
<div class="<?php echo esc_attr($filter_class); ?>"<?php echo $data_attrs; ?>>
    <?php if (!empty($attributes['title'])) : ?>
        <h3 class="hbl-filter-title"><?php echo esc_html($attributes['title']); ?></h3>
    <?php endif; ?>
    <form id="hsf-search-form" class="hbl-advanced-filter-form" method="post" action="#">
        <div class="hbl-filter-fields">
            <?php if ($attributes['showKeywordSearch']) : ?>
                <div class="hbl-filter-field hbl-filter-search">
                    <label for="hsf-filter-search"><?php _e('Search', 'happy-search-and-filter'); ?></label>
                    <input type="text" name="search" id="hsf-filter-search" placeholder="<?php esc_attr_e('Search businesses...', 'happy-search-and-filter'); ?>" value="<?php echo esc_attr($current_search); ?>">
                </div>
            <?php endif; ?>
            <?php if ($attributes['showCategoryFilter'] && !empty($categories)) : ?>
                <div class="hbl-filter-field hbl-filter-category">
                    <label for="hsf-filter-category"><?php _e('Category', 'happy-search-and-filter'); ?></label>
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
            <?php if ($attributes['showLocationFilter'] && !empty($locations)) : ?>
                <div class="hbl-filter-field hbl-filter-location">
                    <label for="hsf-filter-location"><?php _e('Location', 'happy-search-and-filter'); ?></label>
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
                    <label for="hsf-filter-company-type"><?php _e('Company Type', 'happy-search-and-filter'); ?></label>
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
            <?php if ($attributes['showRatingFilter']) : ?>
                <div class="hbl-filter-field hbl-filter-rating">
                    <label for="hsf-filter-rating"><?php _e('Minimum Rating', 'happy-search-and-filter'); ?></label>
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
                    <label for="hsf-filter-price-range"><?php _e('Price Range', 'happy-search-and-filter'); ?></label>
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
                        <?php _e('Verified Businesses Only', 'happy-search-and-filter'); ?>
                    </label>
                </div>
            <?php endif; ?>
            <?php if ($attributes['showDateRangeFilter']) : ?>
                <div class="hbl-filter-field hbl-filter-date-range">
                    <label><?php _e('Date Range', 'happy-search-and-filter'); ?></label>
                    <div class="hbl-date-range-inputs">
                        <input type="text" name="date_from" id="hsf-filter-date-from" class="hbl-datepicker" placeholder="<?php esc_attr_e('From', 'happy-search-and-filter'); ?>" value="<?php echo esc_attr($current_date_from); ?>">
                        <span class="hbl-date-separator">-</span>
                        <input type="text" name="date_to" id="hsf-filter-date-to" class="hbl-datepicker" placeholder="<?php esc_attr_e('To', 'happy-search-and-filter'); ?>" value="<?php echo esc_attr($current_date_to); ?>">
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($attributes['showTagsFilter'] && !empty($tags)) : ?>
                <div class="hbl-filter-field hbl-filter-tags">
                    <label><?php _e('Tags', 'happy-search-and-filter'); ?></label>
                    <div class="hbl-checkbox-group">
                        <?php foreach ($tags as $tag) : ?>
                            <label class="hbl-checkbox-label">
                                <input type="checkbox" name="tags[]" value="<?php echo esc_attr($tag->slug); ?>" <?php checked(in_array($tag->slug, $current_tags)); ?>>
                                <?php echo esc_html($tag->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($attributes['showServiceFilter'] && !empty($services)) : ?>
                <div class="hbl-filter-field hbl-filter-services">
                    <label><?php _e('Services', 'happy-search-and-filter'); ?></label>
                    <div class="hbl-checkbox-group">
                        <?php foreach ($services as $service) : ?>
                            <label class="hbl-checkbox-label">
                                <input type="checkbox" name="services[]" value="<?php echo esc_attr($service); ?>" <?php checked(in_array($service, $current_services)); ?>>
                                <?php echo esc_html($service); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($attributes['showDistanceFilter']) : ?>
                <div class="hbl-filter-field hbl-filter-distance">
                    <label><?php _e('Distance', 'happy-search-and-filter'); ?></label>
                    <div class="hbl-distance-inputs">
                        <input type="text" id="hsf-filter-location-search" placeholder="<?php esc_attr_e('Enter your location', 'happy-search-and-filter'); ?>" class="hbl-location-search">
                        <div class="hbl-distance-selects">
                            <select name="distance" id="hsf-filter-distance">
                                <option value=""><?php _e('Select distance', 'happy-search-and-filter'); ?></option>
                                <?php foreach ($distance_options as $distance) : ?>
                                    <option value="<?php echo esc_attr($distance); ?>" <?php selected($current_distance, $distance); ?>>
                                        <?php echo esc_html($distance); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="distance_unit" id="hsf-filter-distance-unit">
                                <option value="km" <?php selected($current_distance_unit, 'km'); ?>><?php _e('km', 'happy-search-and-filter'); ?></option>
                                <option value="mi" <?php selected($current_distance_unit, 'mi'); ?>><?php _e('miles', 'happy-search-and-filter'); ?></option>
                            </select>
                        </div>
                        <input type="hidden" name="latitude" id="hsf-filter-latitude" value="<?php echo esc_attr($current_latitude); ?>">
                        <input type="hidden" name="longitude" id="hsf-filter-longitude" value="<?php echo esc_attr($current_longitude); ?>">
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($attributes['showOpenNowFilter']) : ?>
                <div class="hbl-filter-field hbl-filter-open-now">
                    <label for="hsf-filter-open-now" class="hbl-checkbox-label">
                        <input type="checkbox" name="open_now" id="hsf-filter-open-now" value="1" <?php checked($current_open_now); ?>>
                        <?php _e('Open Now', 'happy-search-and-filter'); ?>
                    </label>
                </div>
            <?php endif; ?>
            <?php if ($attributes['showSorting']) : ?>
                <div class="hbl-filter-field hbl-filter-sorting">
                    <label for="hsf-filter-orderby"><?php _e('Sort By', 'happy-search-and-filter'); ?></label>
                    <div class="hbl-sorting-selects">
                        <select name="orderby" id="hsf-filter-orderby">
                            <option value="date" <?php selected($current_orderby, 'date'); ?>><?php _e('Date', 'happy-search-and-filter'); ?></option>
                            <option value="title" <?php selected($current_orderby, 'title'); ?>><?php _e('Name', 'happy-search-and-filter'); ?></option>
                            <option value="rating" <?php selected($current_orderby, 'rating'); ?>><?php _e('Rating', 'happy-search-and-filter'); ?></option>
                            <option value="popularity" <?php selected($current_orderby, 'popularity'); ?>><?php _e('Popularity', 'happy-search-and-filter'); ?></option>
                            <option value="price" <?php selected($current_orderby, 'price'); ?>><?php _e('Price', 'happy-search-and-filter'); ?></option>
                        </select>
                        <select name="order" id="hsf-filter-order">
                            <option value="ASC" <?php selected($current_order, 'ASC'); ?>><?php _e('Ascending', 'happy-search-and-filter'); ?></option>
                            <option value="DESC" <?php selected($current_order, 'DESC'); ?>><?php _e('Descending', 'happy-search-and-filter'); ?></option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="hbl-filter-actions">
            <button type="submit" class="hbl-submit-button"><?php _e('Search', 'happy-search-and-filter'); ?></button>
            <button type="button" class="hbl-reset-button"><?php _e('Reset', 'happy-search-and-filter'); ?></button>
        </div>
        <input type="hidden" name="results_per_page" value="<?php echo esc_attr($attributes['resultsPerPage']); ?>">
    </form>
    <div id="hsf-search-results"></div>
</div>
