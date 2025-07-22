<?php
/**
 * Happy Search & Filter - AJAX Handler
 * Handles all AJAX requests for search and filter functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Advanced Search AJAX requests
 * This is the main search handler that connects to our JavaScript
 */
function hsf_advanced_search() {
    // Verify nonce for security
    if (!check_ajax_referer('hsf_advanced_filter_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed. Please refresh the page and try again.', 'happy-search-and-filter')
        ));
    }

    try {
        // Enhanced server-side validation
        $validation_errors = array();
        
        // Sanitize and validate all filter parameters
        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';
        $location = isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : '';
        $company_type = isset($_POST['company_type']) ? sanitize_text_field(wp_unslash($_POST['company_type'])) : '';
        $rating_min = isset($_POST['rating_min']) ? floatval($_POST['rating_min']) : 0;
        $price_range = isset($_POST['price_range']) ? sanitize_text_field(wp_unslash($_POST['price_range'])) : '';
        $verified = isset($_POST['verified']) ? filter_var($_POST['verified'], FILTER_VALIDATE_BOOLEAN) : false;
        $orderby = isset($_POST['orderby']) ? sanitize_text_field(wp_unslash($_POST['orderby'])) : 'date';
        $order = isset($_POST['order']) ? sanitize_text_field(wp_unslash($_POST['order'])) : 'DESC';
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        $results_per_page = isset($_POST['results_per_page']) ? intval($_POST['results_per_page']) : 10;

        // Server-side validation rules
        if (!empty($search)) {
            if (strlen($search) < 2) {
                $validation_errors['search'] = __('Search term must be at least 2 characters', 'happy-search-and-filter');
            } elseif (strlen($search) > 100) {
                $validation_errors['search'] = __('Search term cannot exceed 100 characters', 'happy-search-and-filter');
            } elseif (!preg_match('/^[a-zA-Z0-9\s\-_.,!?()]+$/', $search)) {
                $validation_errors['search'] = __('Search term contains invalid characters', 'happy-search-and-filter');
            }
        }

        if (!empty($location)) {
            if (strlen($location) < 2) {
                $validation_errors['location'] = __('Location must be at least 2 characters', 'happy-search-and-filter');
            } elseif (strlen($location) > 50) {
                $validation_errors['location'] = __('Location cannot exceed 50 characters', 'happy-search-and-filter');
            } elseif (!preg_match('/^[a-zA-Z0-9\s\-_,.()]+$/', $location)) {
                $validation_errors['location'] = __('Location contains invalid characters', 'happy-search-and-filter');
            }
        }

        if ($rating_min > 0) {
            if ($rating_min < 1 || $rating_min > 5) {
                $validation_errors['rating_min'] = __('Rating must be between 1 and 5', 'happy-search-and-filter');
            }
        }

        if ($results_per_page < 1 || $results_per_page > 100) {
            $validation_errors['results_per_page'] = __('Results per page must be between 1 and 100', 'happy-search-and-filter');
        }

        if ($paged < 1 || $paged > 1000) {
            $validation_errors['paged'] = __('Invalid page number', 'happy-search-and-filter');
        }

        // Validate orderby parameter
        $allowed_orderby = array('date', 'title', 'rating', 'popularity', 'price');
        if (!in_array($orderby, $allowed_orderby)) {
            $validation_errors['orderby'] = __('Invalid sort order', 'happy-search-and-filter');
        }

        // Validate order parameter
        $allowed_order = array('ASC', 'DESC');
        if (!in_array(strtoupper($order), $allowed_order)) {
            $validation_errors['order'] = __('Invalid sort direction', 'happy-search-and-filter');
        }

        // If validation errors exist, return them
        if (!empty($validation_errors)) {
            wp_send_json_error(array(
                'message' => __('Please correct the validation errors below.', 'happy-search-and-filter'),
                'validation_errors' => $validation_errors
            ));
        }

        // Check if caching is enabled
        $caching_enabled = HSF_Cache::is_caching_enabled();
        $cache_hit = false;
        
        // Try to get cached results
        if ($caching_enabled) {
            $cached_results = HSF_Cache::get_cached_results($_POST);
            
            if ($cached_results !== false) {
                $cache_hit = true;
                HSF_Cache::update_cache_stats(true);
                
                wp_send_json_success(array(
                    'html' => $cached_results['results']['html'],
                    'count' => $cached_results['results']['count'],
                    'current_page' => $paged,
                    'total_pages' => $cached_results['results']['total_pages'],
                    'cached' => true,
                    'cache_timestamp' => $cached_results['timestamp']
                ));
            }
        }

        // Validate pagination parameters
        $paged = max(1, min($paged, 1000)); // Prevent excessive pagination
        $results_per_page = max(1, min($results_per_page, 100)); // Limit results per page

        // Build WP_Query arguments
        $args = array(
            'post_type' => 'business_listing',
            'post_status' => 'publish',
            'posts_per_page' => $results_per_page,
            'paged' => $paged,
            'meta_query' => array(),
            'tax_query' => array(),
            'no_found_rows' => false, // We need this for pagination
            'update_post_meta_cache' => false, // Performance optimization
            'update_post_term_cache' => false, // Performance optimization
        );

        // Search keyword
        if (!empty($search)) {
            $args['s'] = $search;
        }

        // Category filter
        if (!empty($category)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'business_category',
                'field' => 'slug',
                'terms' => $category
            );
        }

        // Location filter
        if (!empty($location)) {
            $args['meta_query'][] = array(
                'key' => 'location',
                'value' => $location,
                'compare' => 'LIKE'
            );
        }

        // Company type filter
        if (!empty($company_type)) {
            $args['meta_query'][] = array(
                'key' => 'company_type',
                'value' => $company_type,
                'compare' => '='
            );
        }

        // Rating filter
        if ($rating_min > 0) {
            $args['meta_query'][] = array(
                'key' => 'rating',
                'value' => $rating_min,
                'compare' => '>=',
                'type' => 'DECIMAL'
            );
        }

        // Price range filter
        if (!empty($price_range)) {
            $args['meta_query'][] = array(
                'key' => 'price_range',
                'value' => $price_range,
                'compare' => '='
            );
        }

        // Verified filter
        if ($verified) {
            $args['meta_query'][] = array(
                'key' => 'verified',
                'value' => '1',
                'compare' => '='
            );
        }

        // Handle multiple meta queries
        if (count($args['meta_query']) > 1) {
            $args['meta_query']['relation'] = 'AND';
        }

        // Handle multiple tax queries
        if (count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }

        // Ordering
        switch ($orderby) {
            case 'title':
                $args['orderby'] = 'title';
                break;
            case 'rating':
                $args['meta_key'] = 'rating';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'popularity':
                $args['meta_key'] = 'view_count';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'price':
                $args['meta_key'] = 'price_range';
                $args['orderby'] = 'meta_value';
                break;
            default:
                $args['orderby'] = 'date';
        }
        
        $args['order'] = $order;

        // Run the query
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            ob_start();
            
            echo '<div class="hbl-filter-results">';
            echo '<div class="hbl-results-header">';
            echo '<h3>' . sprintf(__('Found %d results', 'happy-search-and-filter'), $query->found_posts) . '</h3>';
            echo '</div>';
            
            echo '<div class="hbl-results-grid">';
            
            while ($query->have_posts()) {
                $query->the_post();
                
                // Get business data
                $business_id = get_the_ID();
                $title = get_the_title();
                $excerpt = get_the_excerpt();
                $permalink = get_permalink();
                
                // Enhanced image handling with responsive sizes and WebP support
                $featured_image = get_the_post_thumbnail_url($business_id, 'medium');
                $featured_image_large = get_the_post_thumbnail_url($business_id, 'large');
                $featured_image_full = get_the_post_thumbnail_url($business_id, 'full');
                
                // Generate WebP versions if supported
                $webp_support = function_exists('imagewebp');
                $featured_image_webp = null;
                $featured_image_large_webp = null;
                
                if ($webp_support && $featured_image) {
                    // Check if WebP version exists or can be generated
                    $featured_image_webp = HSF_Image_Optimizer::get_webp_version($featured_image);
                    $featured_image_large_webp = HSF_Image_Optimizer::get_webp_version($featured_image_large);
                }
                
                // Generate responsive image sizes
                $responsive_images = HSF_Image_Optimizer::generate_responsive_images($business_id);
                
                $rating = get_post_meta($business_id, 'rating', true);
                $location = get_post_meta($business_id, 'location', true);
                $company_type = get_post_meta($business_id, 'company_type', true);
                $verified = get_post_meta($business_id, 'verified', true);
                
                // Categories
                $categories = get_the_terms($business_id, 'business_category');
                $category_names = array();
                if ($categories && !is_wp_error($categories)) {
                    foreach ($categories as $cat) {
                        $category_names[] = $cat->name;
                    }
                }
                
                ?>
                <div class="hbl-business-card">
                    <?php if ($featured_image) : ?>
                        <div class="hbl-business-image">
                            <a href="<?php echo esc_url($permalink); ?>">
                                <?php 
                                // Use optimized picture element with WebP support
                                if (!empty($responsive_images)) {
                                    echo HSF_Image_Optimizer::generate_picture_element(
                                        $responsive_images, 
                                        esc_attr($title), 
                                        'hbl-lazy-image'
                                    );
                                } else {
                                    // Fallback to simple image with lazy loading
                                    ?>
                                    <img src="<?php echo esc_url($featured_image); ?>" 
                                         data-src="<?php echo esc_url($featured_image_large); ?>"
                                         data-srcset="<?php echo esc_url($featured_image); ?> 300w, <?php echo esc_url($featured_image_large); ?> 600w"
                                         data-sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
                                         alt="<?php echo esc_attr($title); ?>" 
                                         loading="lazy"
                                         class="hbl-lazy-image">
                                    <?php
                                }
                                ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <!-- Skeleton loading for missing images -->
                        <div class="hbl-business-image">
                            <a href="<?php echo esc_url($permalink); ?>">
                                <?php echo HSF_Image_Optimizer::generate_skeleton_html(); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hbl-business-content">
                        <h4 class="hbl-business-title">
                            <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                            <?php if ($verified) : ?>
                                <span class="hbl-verified-badge" title="<?php esc_attr_e('Verified Business', 'happy-search-and-filter'); ?>">✓</span>
                            <?php endif; ?>
                        </h4>
                        
                        <?php if ($rating) : ?>
                            <div class="hbl-business-rating">
                                <span class="hbl-stars">
                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <span class="hbl-star <?php echo $i <= $rating ? 'hbl-star-filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </span>
                                <span class="hbl-rating-text"><?php echo esc_html($rating); ?>/5</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($category_names)) : ?>
                            <div class="hbl-business-categories">
                                <?php echo esc_html(implode(', ', $category_names)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($location) : ?>
                            <div class="hbl-business-location">
                                📍 <?php echo esc_html($location); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($company_type) : ?>
                            <div class="hbl-business-type">
                                <?php echo esc_html($company_type); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="hbl-business-excerpt" data-content="<?php echo esc_attr(wp_trim_words($excerpt, 20)); ?>">
                            <?php echo wp_trim_words($excerpt, 20); ?>
                        </div>
                        
                        <a href="<?php echo esc_url($permalink); ?>" class="hbl-view-details">
                            <?php _e('View Details', 'happy-search-and-filter'); ?>
                        </a>
                    </div>
                </div>
                <?php
            }
            
            echo '</div>'; // .hbl-results-grid
            
            // Pagination
            if ( $query->max_num_pages > 1 ) {
                echo '<div class="hbl-pagination">';
                
                // Check if we should show load more button or traditional pagination
                $pagination_type = isset($_POST['pagination_type']) ? sanitize_text_field($_POST['pagination_type']) : 'traditional';
                
                if ($pagination_type === 'load_more' || $paged < $query->max_num_pages) {
                    // Load More button
                    echo '<button type="button" class="hbl-load-more hbl-submit-button">';
                    echo '<svg class="hbl-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
                    echo '<path d="M12 5v14M5 12h14"></path>';
                    echo '</svg>';
                    echo sprintf(__('Load More (%d of %d)', 'happy-search-and-filter'), $paged, $query->max_num_pages);
                    echo '</button>';
                    
                    // Show current page info
                    echo '<div class="hbl-pagination-info">';
                    echo sprintf(__('Page %d of %d', 'happy-search-and-filter'), $paged, $query->max_num_pages);
                    echo '</div>';
                } else {
                    // Traditional pagination
                    echo paginate_links( array(
                        'base' => '#',
                        'format' => '?paged=%#%',
                        'current' => $paged,
                        'total' => $query->max_num_pages,
                        'prev_text' => __( '← Previous', 'happy-search-and-filter' ),
                        'next_text' => __( 'Next →', 'happy-search-and-filter' ),
                        'type' => 'array'
                    ) );
                }
                
                echo '</div>';
            }
            
            echo '</div>'; // .hbl-filter-results
            
            $html = ob_get_clean();
            wp_reset_postdata();
            
            // Cache the results if caching is enabled
            if ($caching_enabled && !$cache_hit) {
                $cache_expiration = HSF_Cache::get_cache_expiration($_POST);
                $cache_data = array(
                    'html' => $html,
                    'count' => $query->found_posts,
                    'total_pages' => $query->max_num_pages
                );
                
                HSF_Cache::cache_results($_POST, $cache_data, $cache_expiration);
                HSF_Cache::update_cache_stats(false);
            }
            
            wp_send_json_success(array(
                'html' => $html,
                'count' => $query->found_posts,
                'current_page' => $paged,
                'total_pages' => $query->max_num_pages,
                'cached' => $cache_hit,
                'cache_timestamp' => $cache_hit ? time() : null
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No results found. Try adjusting your filters.', 'happy-search-and-filter'),
                'html' => '<div class="hbl-no-results"><p>' . __('No results found. Try adjusting your filters.', 'happy-search-and-filter') . '</p></div>'
            ));
        }

    } catch (Exception $e) {
        // Log error for debugging
        error_log('HSF Search Error: ' . $e->getMessage());
        
        wp_send_json_error(array(
            'message' => __('An error occurred while searching. Please try again.', 'happy-search-and-filter')
        ));
    }

    wp_die();
}

// Register AJAX actions for both logged-in and non-logged-in users
add_action('wp_ajax_hsf_advanced_search', 'hsf_advanced_search');
add_action('wp_ajax_nopriv_hsf_advanced_search', 'hsf_advanced_search');

/**
 * Handle basic search AJAX requests (legacy support)
 */
function hsf_ajax_handler() {
    // Nonce check for security
    if (!check_ajax_referer('hsf_search_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed. Please refresh the page and try again.', 'happy-search-and-filter')
        ));
    }

    try {
        // Sanitize request parameters
        $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
        $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';
        $location = isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : '';
        $date_range = isset($_POST['date_range']) ? sanitize_text_field(wp_unslash($_POST['date_range'])) : '';

        // Build WP_Query arguments
        $args = array(
            'post_type' => 'business_listing',
            'post_status' => 'publish',
            'posts_per_page' => 10,
        );

        if ($keyword) {
            $args['s'] = $keyword;
        }

        if ($category) {
            $args['tax_query'][] = array(
                'taxonomy' => 'business_category',
                'field' => 'slug',
                'terms' => $category,
            );
        }

        if ($location) {
            $args['meta_query'][] = array(
                'key' => 'location',
                'value' => $location,
                'compare' => 'LIKE',
            );
        }

        if ($date_range) {
            $args['date_query'][] = array(
                'after' => $date_range,
                'inclusive' => true,
            );
        }

        // Run the query
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $results = array();

            while ($query->have_posts()) {
                $query->the_post();

                $results[] = array(
                    'title' => esc_html(get_the_title()),
                    'link' => esc_url(get_permalink()),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 20),
                );
            }

            wp_reset_postdata();
            wp_send_json_success($results);
        } else {
            wp_send_json_error(__('No results found.', 'happy-search-and-filter'));
        }

    } catch (Exception $e) {
        error_log('HSF Basic Search Error: ' . $e->getMessage());
        wp_send_json_error(__('An error occurred while searching. Please try again.', 'happy-search-and-filter'));
    }

    wp_die();
}

add_action('wp_ajax_hsf_search', 'hsf_ajax_handler');
add_action('wp_ajax_nopriv_hsf_search', 'hsf_ajax_handler');

/**
 * Handle Advanced Filter AJAX requests (legacy support)
 */
function hsf_advanced_filter_results() {
    // Redirect to the new handler for consistency
    hsf_advanced_search();
}

add_action('wp_ajax_hsf_advanced_filter_results', 'hsf_advanced_filter_results');
add_action('wp_ajax_nopriv_hsf_advanced_filter_results', 'hsf_advanced_filter_results');

/**
 * Image Optimization Helper Methods
 */
class HSF_Image_Optimizer {
    
    /**
     * Get WebP version of an image if available
     */
    public static function get_webp_version($image_url) {
        if (!$image_url) {
            return null;
        }
        
        // Check if WebP version exists
        $webp_url = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_url);
        
        // Check if file exists
        $webp_path = str_replace(site_url('/'), ABSPATH, $webp_url);
        if (file_exists($webp_path)) {
            return $webp_url;
        }
        
        // Try to generate WebP version
        return self::generate_webp_version($image_url);
    }
    
    /**
     * Generate WebP version of an image
     */
    public static function generate_webp_version($image_url) {
        if (!function_exists('imagewebp')) {
            return null;
        }
        
        $image_path = str_replace(site_url('/'), ABSPATH, $image_url);
        
        if (!file_exists($image_path)) {
            return null;
        }
        
        $image_info = getimagesize($image_path);
        if (!$image_info) {
            return null;
        }
        
        $mime_type = $image_info['mime'];
        $width = $image_info[0];
        $height = $image_info[1];
        
        // Create image resource based on type
        switch ($mime_type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($image_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($image_path);
                break;
            default:
                return null;
        }
        
        if (!$image) {
            return null;
        }
        
        // Generate WebP path
        $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_path);
        $webp_url = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_url);
        
        // Save WebP version
        $quality = 85; // Good balance between quality and size
        if (imagewebp($image, $webp_path, $quality)) {
            imagedestroy($image);
            return $webp_url;
        }
        
        imagedestroy($image);
        return null;
    }
    
    /**
     * Generate responsive image sizes for a business
     */
    public static function generate_responsive_images($business_id) {
        $images = array();
        
        // Define responsive sizes
        $sizes = array(
            'thumbnail' => array(150, 150),
            'small' => array(300, 200),
            'medium' => array(600, 400),
            'large' => array(900, 600),
            'full' => array(1200, 800)
        );
        
        foreach ($sizes as $size_name => $dimensions) {
            $image_url = get_the_post_thumbnail_url($business_id, $size_name);
            if ($image_url) {
                $images[$size_name] = array(
                    'url' => $image_url,
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                    'webp' => self::get_webp_version($image_url)
                );
            }
        }
        
        return $images;
    }
    
    /**
     * Generate srcset attribute for responsive images
     */
    public static function generate_srcset($responsive_images) {
        $srcset_parts = array();
        
        foreach ($responsive_images as $size => $image_data) {
            if ($image_data['url']) {
                $srcset_parts[] = $image_data['url'] . ' ' . $image_data['width'] . 'w';
            }
        }
        
        return implode(', ', $srcset_parts);
    }
    
    /**
     * Generate picture element with WebP support
     */
    public static function generate_picture_element($responsive_images, $alt_text, $class = '') {
        if (empty($responsive_images)) {
            return '';
        }
        
        $fallback_url = reset($responsive_images)['url'];
        $srcset = self::generate_srcset($responsive_images);
        
        // Generate WebP srcset
        $webp_srcset_parts = array();
        foreach ($responsive_images as $image_data) {
            if ($image_data['webp']) {
                $webp_srcset_parts[] = $image_data['webp'] . ' ' . $image_data['width'] . 'w';
            }
        }
        $webp_srcset = implode(', ', $webp_srcset_parts);
        
        $picture_html = '<picture class="' . esc_attr($class) . '">';
        
        // WebP source if available
        if (!empty($webp_srcset_parts)) {
            $picture_html .= '<source srcset="' . esc_attr($webp_srcset) . '" type="image/webp">';
        }
        
        // Fallback image
        $picture_html .= '<img src="' . esc_url($fallback_url) . '" 
                               srcset="' . esc_attr($srcset) . '" 
                               sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw" 
                               alt="' . esc_attr($alt_text) . '" 
                               loading="lazy" 
                               class="hbl-lazy-image">';
        
        $picture_html .= '</picture>';
        
        return $picture_html;
    }
    
    /**
     * Generate skeleton loading HTML
     */
    public static function generate_skeleton_html() {
        return '<div class="hbl-skeleton-image">
                    <div class="hbl-skeleton-shimmer"></div>
                </div>';
    }
    
    /**
     * Check if image exists and is accessible
     */
    public static function image_exists($image_url) {
        if (!$image_url) {
            return false;
        }
        
        $image_path = str_replace(site_url('/'), ABSPATH, $image_url);
        return file_exists($image_path);
    }
    
    /**
     * Get optimized image dimensions
     */
    public static function get_optimized_dimensions($original_width, $original_height, $max_width, $max_height) {
        $ratio = min($max_width / $original_width, $max_height / $original_height);
        
        return array(
            'width' => round($original_width * $ratio),
            'height' => round($original_height * $ratio)
        );
    }
}
?>
