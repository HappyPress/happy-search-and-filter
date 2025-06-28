<?php
// Handle AJAX requests
function hsf_ajax_handler() {
    // Nonce check for security.
    check_ajax_referer( 'hsf_search_nonce', 'nonce' );

    // Capability check – search is public, but you may restrict if needed.

    // Sanitize request parameters.
    $keyword    = isset( $_POST['keyword'] )   ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) )   : '';
    $category   = isset( $_POST['category'] )  ? sanitize_text_field( wp_unslash( $_POST['category'] ) )  : '';
    $location   = isset( $_POST['location'] )  ? sanitize_text_field( wp_unslash( $_POST['location'] ) )  : '';
    $date_range = isset( $_POST['date_range'] ) ? sanitize_text_field( wp_unslash( $_POST['date_range'] ) ) : '';

    // Build WP_Query arguments.
    $args = array(
        'post_type'      => 'business_listing',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
    );

    if ( $keyword ) {
        $args['s'] = $keyword;
    }

    if ( $category ) {
        $args['tax_query'][] = array(
            'taxonomy' => 'business_category',
            'field'    => 'slug',
            'terms'    => $category,
        );
    }

    if ( $location ) {
        $args['meta_query'][] = array(
            'key'     => 'location',
            'value'   => $location,
            'compare' => 'LIKE',
        );
    }

    if ( $date_range ) {
        $args['date_query'][] = array(
            'after'     => $date_range,
            'inclusive' => true,
        );
    }

    // Run the query.
    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        $results = array();

        while ( $query->have_posts() ) {
            $query->the_post();

            $results[] = array(
                'title'   => esc_html( get_the_title() ),
                'link'    => esc_url( get_permalink() ),
                'excerpt' => wp_trim_words( get_the_excerpt(), 20 ),
            );
        }

        wp_reset_postdata();

        wp_send_json_success( $results );
    } else {
        wp_send_json_error( __( 'No results found.', 'happy-search-and-filter' ) );
    }

    wp_die();
}

add_action( 'wp_ajax_hsf_search', 'hsf_ajax_handler' );
add_action( 'wp_ajax_nopriv_hsf_search', 'hsf_ajax_handler' );

/**
 * Handle Advanced Filter AJAX requests
 */
function hsf_advanced_filter_results() {
    // Nonce check for security
    check_ajax_referer( 'hsf_advanced_filter_nonce', 'nonce' );

    // Sanitize and validate all filter parameters
    $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
    $category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
    $location = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '';
    $company_type = isset( $_POST['company_type'] ) ? sanitize_text_field( wp_unslash( $_POST['company_type'] ) ) : '';
    $rating_min = isset( $_POST['rating_min'] ) ? floatval( $_POST['rating_min'] ) : 0;
    $price_range = isset( $_POST['price_range'] ) ? sanitize_text_field( wp_unslash( $_POST['price_range'] ) ) : '';
    $verified = isset( $_POST['verified'] ) ? filter_var( $_POST['verified'], FILTER_VALIDATE_BOOLEAN ) : false;
    $orderby = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'date';
    $order = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC';
    $date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
    $date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
    $services = isset( $_POST['services'] ) ? (array) $_POST['services'] : array();
    $tags = isset( $_POST['tags'] ) ? (array) $_POST['tags'] : array();
    $distance = isset( $_POST['distance'] ) ? intval( $_POST['distance'] ) : 0;
    $latitude = isset( $_POST['latitude'] ) ? floatval( $_POST['latitude'] ) : 0;
    $longitude = isset( $_POST['longitude'] ) ? floatval( $_POST['longitude'] ) : 0;
    $open_now = isset( $_POST['open_now'] ) ? filter_var( $_POST['open_now'], FILTER_VALIDATE_BOOLEAN ) : false;
    $paged = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
    $results_per_page = isset( $_POST['results_per_page'] ) ? intval( $_POST['results_per_page'] ) : 10;

    // Build WP_Query arguments
    $args = array(
        'post_type' => 'business_listing',
        'post_status' => 'publish',
        'posts_per_page' => $results_per_page,
        'paged' => $paged,
        'meta_query' => array(),
        'tax_query' => array(),
        'date_query' => array()
    );

    // Search keyword
    if ( ! empty( $search ) ) {
        $args['s'] = $search;
    }

    // Category filter
    if ( ! empty( $category ) ) {
        $args['tax_query'][] = array(
            'taxonomy' => 'business_category',
            'field' => 'slug',
            'terms' => $category
        );
    }

    // Location filter
    if ( ! empty( $location ) ) {
        $args['meta_query'][] = array(
            'key' => 'location',
            'value' => $location,
            'compare' => 'LIKE'
        );
    }

    // Company type filter
    if ( ! empty( $company_type ) ) {
        $args['meta_query'][] = array(
            'key' => 'company_type',
            'value' => $company_type,
            'compare' => '='
        );
    }

    // Rating filter
    if ( $rating_min > 0 ) {
        $args['meta_query'][] = array(
            'key' => 'rating',
            'value' => $rating_min,
            'compare' => '>=',
            'type' => 'DECIMAL'
        );
    }

    // Price range filter
    if ( ! empty( $price_range ) ) {
        $args['meta_query'][] = array(
            'key' => 'price_range',
            'value' => $price_range,
            'compare' => '='
        );
    }

    // Verified filter
    if ( $verified ) {
        $args['meta_query'][] = array(
            'key' => 'verified',
            'value' => '1',
            'compare' => '='
        );
    }

    // Date range filter
    if ( ! empty( $date_from ) || ! empty( $date_to ) ) {
        $date_query = array();
        
        if ( ! empty( $date_from ) ) {
            $date_query['after'] = $date_from;
        }
        
        if ( ! empty( $date_to ) ) {
            $date_query['before'] = $date_to;
        }
        
        $date_query['inclusive'] = true;
        $args['date_query'][] = $date_query;
    }

    // Services filter
    if ( ! empty( $services ) ) {
        $services_query = array( 'relation' => 'OR' );
        foreach ( $services as $service ) {
            $services_query[] = array(
                'key' => 'services',
                'value' => sanitize_text_field( $service ),
                'compare' => 'LIKE'
            );
        }
        $args['meta_query'][] = $services_query;
    }

    // Tags filter
    if ( ! empty( $tags ) ) {
        $args['tax_query'][] = array(
            'taxonomy' => 'business_tag',
            'field' => 'slug',
            'terms' => array_map( 'sanitize_text_field', $tags )
        );
    }

    // Distance filter (if coordinates provided)
    if ( $distance > 0 && $latitude != 0 && $longitude != 0 ) {
        // This would require a custom SQL query for distance calculation
        // For now, we'll skip distance filtering in the basic implementation
        // TODO: Implement distance-based filtering
    }

    // Open now filter
    if ( $open_now ) {
        // This would require checking business hours
        // For now, we'll skip this filter in the basic implementation
        // TODO: Implement open now filtering
    }

    // Ordering
    switch ( $orderby ) {
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
    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        ob_start();
        
        echo '<div class="hbl-filter-results">';
        echo '<div class="hbl-results-header">';
        echo '<h3>' . sprintf( __( 'Found %d results', 'happy-search-and-filter' ), $query->found_posts ) . '</h3>';
        echo '</div>';
        
        echo '<div class="hbl-results-grid">';
        
        while ( $query->have_posts() ) {
            $query->the_post();
            
            // Get business data
            $business_id = get_the_ID();
            $title = get_the_title();
            $excerpt = get_the_excerpt();
            $permalink = get_permalink();
            $featured_image = get_the_post_thumbnail_url( $business_id, 'medium' );
            $rating = get_post_meta( $business_id, 'rating', true );
            $location = get_post_meta( $business_id, 'location', true );
            $company_type = get_post_meta( $business_id, 'company_type', true );
            $verified = get_post_meta( $business_id, 'verified', true );
            
            // Categories
            $categories = get_the_terms( $business_id, 'business_category' );
            $category_names = array();
            if ( $categories && ! is_wp_error( $categories ) ) {
                foreach ( $categories as $cat ) {
                    $category_names[] = $cat->name;
                }
            }
            
            ?>
            <div class="hbl-business-card">
                <?php if ( $featured_image ) : ?>
                    <div class="hbl-business-image">
                        <a href="<?php echo esc_url( $permalink ); ?>">
                            <img src="<?php echo esc_url( $featured_image ); ?>" alt="<?php echo esc_attr( $title ); ?>">
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="hbl-business-content">
                    <h4 class="hbl-business-title">
                        <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
                        <?php if ( $verified ) : ?>
                            <span class="hbl-verified-badge" title="<?php esc_attr_e( 'Verified Business', 'happy-search-and-filter' ); ?>">✓</span>
                        <?php endif; ?>
                    </h4>
                    
                    <?php if ( $rating ) : ?>
                        <div class="hbl-business-rating">
                            <span class="hbl-stars">
                                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                    <span class="hbl-star <?php echo $i <= $rating ? 'hbl-star-filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </span>
                            <span class="hbl-rating-text"><?php echo esc_html( $rating ); ?>/5</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $category_names ) ) : ?>
                        <div class="hbl-business-categories">
                            <?php echo esc_html( implode( ', ', $category_names ) ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( $location ) : ?>
                        <div class="hbl-business-location">
                            📍 <?php echo esc_html( $location ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( $company_type ) : ?>
                        <div class="hbl-business-type">
                            <?php echo esc_html( $company_type ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hbl-business-excerpt">
                        <?php echo wp_trim_words( $excerpt, 20 ); ?>
                    </div>
                    
                    <a href="<?php echo esc_url( $permalink ); ?>" class="hbl-view-details">
                        <?php _e( 'View Details', 'happy-search-and-filter' ); ?>
                    </a>
                </div>
            </div>
            <?php
        }
        
        echo '</div>'; // .hbl-results-grid
        
        // Pagination
        if ( $query->max_num_pages > 1 ) {
            echo '<div class="hbl-pagination">';
            echo paginate_links( array(
                'base' => '#',
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $query->max_num_pages,
                'prev_text' => __( '← Previous', 'happy-search-and-filter' ),
                'next_text' => __( 'Next →', 'happy-search-and-filter' )
            ) );
            echo '</div>';
        }
        
        echo '</div>'; // .hbl-filter-results
        
        $html = ob_get_clean();
        wp_reset_postdata();
        
        wp_send_json_success( array( 'html' => $html ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'No results found. Try adjusting your filters.', 'happy-search-and-filter' ) ) );
    }

    wp_die();
}

add_action( 'wp_ajax_hsf_advanced_filter_results', 'hsf_advanced_filter_results' );
add_action( 'wp_ajax_nopriv_hsf_advanced_filter_results', 'hsf_advanced_filter_results' );
?>
