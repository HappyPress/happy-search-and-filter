<?php
/**
 * Advanced Search Gutenberg Block
 *
 * @package Happy_Search_And_Filter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Advanced Search Block
 */
function hsf_register_advanced_search_block() {
    // Register block editor script
    wp_register_script(
        'hsf-advanced-search-editor',
        HSF_PLUGIN_URL . 'assets/js/advanced-search-editor.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
        HSF_VERSION,
        true
    );

    // Register block editor style
    wp_register_style(
        'hsf-advanced-search-editor',
        HSF_PLUGIN_URL . 'assets/css/advanced-search-editor.css',
        array('wp-edit-blocks'),
        HSF_VERSION
    );

    // Register block
    register_block_type('happy-search-and-filter/business-search', array(
        'editor_script' => 'hsf-advanced-search-editor',
        'editor_style'  => 'hsf-advanced-search-editor',
        'render_callback' => 'hsf_render_advanced_search_block',
        'attributes' => array(
            'showSearch' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showCategoryFilter' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showLocationFilter' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showRatingFilter' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showVerifiedFilter' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showSortOptions' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'filterStyle' => array(
                'type' => 'string',
                'default' => 'horizontal'
            ),
            'savedFilterId' => array(
                'type' => 'number',
                'default' => 0
            ),
            'className' => array(
                'type' => 'string'
            )
        )
    ));
}
add_action('init', 'hsf_register_advanced_search_block');

/**
 * Render Advanced Search Block
 */
function hsf_render_advanced_search_block($attributes) {
    // Set defaults
    $defaults = array(
        'showSearch' => true,
        'showCategoryFilter' => true,
        'showLocationFilter' => true,
        'showRatingFilter' => true,
        'showVerifiedFilter' => true,
        'showSortOptions' => true,
        'filterStyle' => 'horizontal',
        'savedFilterId' => 0,
        'className' => ''
    );

    $attributes = array_merge($defaults, $attributes);

    ob_start();

    // Add wrapper with block-specific classes
    $wrapper_classes = array('hsf-advanced-search-block');

    if (!empty($attributes['className'])) {
        $wrapper_classes[] = $attributes['className'];
    }

    $wrapper_classes[] = 'hsf-style-' . $attributes['filterStyle'];

    echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '">';

    // If a saved filter is selected, use it
    if ($attributes['savedFilterId'] > 0) {
        echo hsf_render_saved_filter($attributes['savedFilterId']);
    } else {
        // Render custom filter based on settings
        echo hsf_render_custom_filter($attributes);
    }

    echo '</div>';

    return ob_get_clean();
}

/**
 * Render custom filter based on block attributes
 */
function hsf_render_custom_filter($attributes) {
    ob_start();
    ?>
    <div class="hsf-custom-filter">
        <form class="hsf-filter-form" method="get">
            <?php if ($attributes['showSearch']) : ?>
                <div class="hsf-filter-group hsf-search-group">
                    <label for="hsf-search"><?php _e('Search', 'happy-search-and-filter'); ?></label>
                    <input type="text" id="hsf-search" name="search" value="<?php echo esc_attr(get_query_var('search')); ?>" placeholder="<?php _e('Search businesses...', 'happy-search-and-filter'); ?>">
                </div>
            <?php endif; ?>

            <?php if ($attributes['showCategoryFilter']) : ?>
                <div class="hsf-filter-group hsf-category-group">
                    <label for="hsf-category"><?php _e('Category', 'happy-search-and-filter'); ?></label>
                    <select id="hsf-category" name="business_category">
                        <option value=""><?php _e('All Categories', 'happy-search-and-filter'); ?></option>
                        <?php
                        $categories = get_terms(array(
                            'taxonomy' => 'business_category',
                            'hide_empty' => false
                        ));

                        if (!is_wp_error($categories)) {
                            foreach ($categories as $category) {
                                $selected = (get_query_var('business_category') == $category->slug) ? 'selected' : '';
                                echo '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if ($attributes['showLocationFilter']) : ?>
                <div class="hsf-filter-group hsf-location-group">
                    <label for="hsf-location"><?php _e('Location', 'happy-search-and-filter'); ?></label>
                    <input type="text" id="hsf-location" name="location" value="<?php echo esc_attr(get_query_var('location')); ?>" placeholder="<?php _e('Enter location...', 'happy-search-and-filter'); ?>">
                </div>
            <?php endif; ?>

            <?php if ($attributes['showRatingFilter']) : ?>
                <div class="hsf-filter-group hsf-rating-group">
                    <label for="hsf-rating"><?php _e('Minimum Rating', 'happy-search-and-filter'); ?></label>
                    <select id="hsf-rating" name="min_rating">
                        <option value=""><?php _e('Any Rating', 'happy-search-and-filter'); ?></option>
                        <option value="4" <?php selected(get_query_var('min_rating'), '4'); ?>>4+ Stars</option>
                        <option value="3" <?php selected(get_query_var('min_rating'), '3'); ?>>3+ Stars</option>
                        <option value="2" <?php selected(get_query_var('min_rating'), '2'); ?>>2+ Stars</option>
                        <option value="1" <?php selected(get_query_var('min_rating'), '1'); ?>>1+ Stars</option>
                    </select>
                </div>
            <?php endif; ?>

            <?php if ($attributes['showVerifiedFilter']) : ?>
                <div class="hsf-filter-group hsf-verified-group">
                    <label>
                        <input type="checkbox" name="verified_only" value="1" <?php checked(get_query_var('verified_only'), '1'); ?>>
                        <?php _e('Verified businesses only', 'happy-search-and-filter'); ?>
                    </label>
                </div>
            <?php endif; ?>

            <?php if ($attributes['showSortOptions']) : ?>
                <div class="hsf-filter-group hsf-sort-group">
                    <label for="hsf-sort"><?php _e('Sort by', 'happy-search-and-filter'); ?></label>
                    <select id="hsf-sort" name="sort_by">
                        <option value="date" <?php selected(get_query_var('sort_by'), 'date'); ?>><?php _e('Newest First', 'happy-search-and-filter'); ?></option>
                        <option value="title" <?php selected(get_query_var('sort_by'), 'title'); ?>><?php _e('Name A-Z', 'happy-search-and-filter'); ?></option>
                        <option value="rating" <?php selected(get_query_var('sort_by'), 'rating'); ?>><?php _e('Highest Rated', 'happy-search-and-filter'); ?></option>
                        <option value="random" <?php selected(get_query_var('sort_by'), 'random'); ?>><?php _e('Random', 'happy-search-and-filter'); ?></option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="hsf-filter-actions">
                <button type="submit" class="hsf-filter-submit"><?php _e('Filter Results', 'happy-search-and-filter'); ?></button>
                <button type="button" class="hsf-filter-reset"><?php _e('Clear Filters', 'happy-search-and-filter'); ?></button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Add block category for HSF
 */
function hsf_add_block_category($categories) {
    // Check if Business Directory category already exists (from HBL)
    $business_category_exists = false;
    foreach ($categories as $category) {
        if ($category['slug'] === 'happy-business-listing') {
            $business_category_exists = true;
            break;
        }
    }

    // If it doesn't exist, add our own
    if (!$business_category_exists) {
        return array_merge(
            array(
                array(
                    'slug'  => 'happy-search-filter',
                    'title' => __('Search & Filter', 'happy-search-and-filter'),
                    'icon'  => 'search'
                )
            ),
            $categories
        );
    }

    return $categories;
}
add_filter('block_categories_all', 'hsf_add_block_category');

/**
 * Enqueue block editor assets for HSF
 */
function hsf_enqueue_block_editor_assets() {
    // Localize script for editor
    wp_localize_script('hsf-advanced-search-editor', 'hsfBlockData', array(
        'savedFilters' => hsf_get_saved_filters_for_editor(),
        'pluginUrl' => HSF_PLUGIN_URL,
        'nonce' => wp_create_nonce('hsf_block_nonce')
    ));
}
add_action('enqueue_block_editor_assets', 'hsf_enqueue_block_editor_assets');

/**
 * Get saved filters for block editor
 */
function hsf_get_saved_filters_for_editor() {
    $saved_filters = get_posts(array(
        'post_type' => 'hsf_saved_filter',
        'post_status' => 'publish',
        'numberposts' => -1
    ));

    $filter_options = array(
        array(
            'label' => __('Custom Filter', 'happy-search-and-filter'),
            'value' => 0
        )
    );

    foreach ($saved_filters as $filter) {
        $filter_options[] = array(
            'label' => $filter->post_title,
            'value' => $filter->ID
        );
    }

    return $filter_options;
}
?>