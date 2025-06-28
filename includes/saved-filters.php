<?php
// Abort direct
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get filters AJAX.
 */
function hsf_ajax_get_filters() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_success( array() );
    }

    wp_send_json_success( hsf_get_saved_filters() );
}
add_action( 'wp_ajax_hsf_get_filters', 'hsf_ajax_get_filters' );

/**
 * Save filter AJAX.
 */
function hsf_ajax_save_filter() {
    check_ajax_referer( 'hsf_saved_filter_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( __( 'Login required.', 'happy-search-and-filter' ) );
    }

    $user_id = get_current_user_id();

    $name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $params = isset( $_POST['params'] ) ? wp_unslash( $_POST['params'] ) : array();

    if ( empty( $name ) || empty( $params ) || ! is_array( $params ) ) {
        wp_send_json_error( __( 'Invalid data.', 'happy-search-and-filter' ) );
    }

    $filter = array(
        'id'      => isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : wp_generate_uuid4(),
        'name'    => $name,
        'params'  => array_map( 'sanitize_text_field', $params ),
        'created' => time(),
    );

    $result = hsf_save_filter( $user_id, $filter );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( hsf_get_saved_filters( $user_id ) );
}
add_action( 'wp_ajax_hsf_save_filter', 'hsf_ajax_save_filter' );

/**
 * Delete filter AJAX.
 */
function hsf_ajax_delete_filter() {
    check_ajax_referer( 'hsf_saved_filter_nonce', 'nonce' );

    $filter_id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';

    if ( ! $filter_id || ! is_user_logged_in() ) {
        wp_send_json_error();
    }

    hsf_delete_filter( get_current_user_id(), $filter_id );

    wp_send_json_success();
}
add_action( 'wp_ajax_hsf_delete_filter', 'hsf_ajax_delete_filter' ); 