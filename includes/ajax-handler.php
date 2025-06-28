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
?>
