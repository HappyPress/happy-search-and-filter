<?php
// Handle AJAX requests
function hsf_ajax_handler() {
    check_ajax_referer('hsf_search_nonce', 'nonce');
    $keyword = sanitize_text_field($_POST['keyword']);
    $category = sanitize_text_field($_POST['category']);
    $location = sanitize_text_field($_POST['location']);
    $date_range = sanitize_text_field($_POST['date_range']);

    // Construct query arguments
    $args = array(
        'post_type' => 'business_listing',
        's' => $keyword,
        'tax_query' => array(
            array(
                'taxonomy' => 'business_category',
                'field' => 'slug',
                'terms' => $category,
            ),
        ),
        'meta_query' => array(
            array(
                'key' => 'location',
                'value' => $location,
                'compare' => 'LIKE',
            ),
        ),
        'date_query' => array(
            array(
                'after' => $date_range,
            ),
        ),
    );

    // Execute the query
    $query = new WP_Query($args);

    // Check if any posts were found
    if ($query->have_posts()) {
        $results = array();
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = array(
                'title' => get_the_title(),
                'link' => get_permalink(),
                'excerpt' => get_the_excerpt(),
            );
        }
        wp_send_json_success($results);
    } else {
        wp_send_json_error('No results found.');
    }
    wp_die();
}
add_action('wp_ajax_hsf_search', 'hsf_ajax_handler');
add_action('wp_ajax_nopriv_hsf_search', 'hsf_ajax_handler');
?>
