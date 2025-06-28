<?php
/**
 * Helper / utility functions for Happy Search & Filter
 *
 * @package HappySearchAndFilter
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitize a checkbox value (on/off).
 *
 * @param mixed $value Raw input.
 * @return string 'on' when checked; '' otherwise.
 */
function hsf_sanitize_checkbox( $value ) {
    return isset( $value ) && ( 'on' === $value || 1 === $value || '1' === $value ) ? 'on' : '';
}

/**
 * Simple text sanitization.
 *
 * @param string $value Raw input.
 * @return string Sanitized.
 */
function hsf_sanitize_text( $value ) {
    return sanitize_text_field( $value );
}

/**
 * Sanitize integer settings (e.g. results per page).
 *
 * @param mixed $value Raw input.
 * @return int Positive integer.
 */
function hsf_sanitize_int( $value ) {
    return absint( $value );
}

/**
 * Retrieve saved filters for a user.
 *
 * @param int $user_id User ID. Defaults to current user.
 * @return array
 */
function hsf_get_saved_filters( $user_id = 0 ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    if ( ! $user_id ) {
        return array();
    }

    $filters = get_user_meta( $user_id, 'hsf_saved_filters', true );

    $filters = is_array( $filters ) ? $filters : array();

    // Allow other plugins to modify or provide their own store.
    return apply_filters( 'hsf_get_saved_filters', $filters, $user_id );
}

/**
 * Save (create or update) a filter for the user.
 *
 * @param int   $user_id User ID.
 * @param array $filter  Filter data (requires id, name, params).
 * @return bool|WP_Error
 */
function hsf_save_filter( $user_id, array $filter ) {
    if ( ! $user_id ) {
        return new WP_Error( 'invalid_user', __( 'Invalid user.', 'happy-search-and-filter' ) );
    }

    if ( empty( $filter['id'] ) ) {
        $filter['id'] = wp_generate_uuid4();
    }

    $filters = hsf_get_saved_filters( $user_id );

    // Replace or append.
    $found = false;
    foreach ( $filters as $idx => $existing ) {
        if ( $existing['id'] === $filter['id'] ) {
            $filters[ $idx ] = $filter;
            $found           = true;
            break;
        }
    }

    if ( ! $found ) {
        $filters[] = $filter;
    }

    // Give other plugins a chance to intercept saving logic.
    $filters = apply_filters( 'hsf_pre_save_filters', $filters, $user_id, $filter );

    update_user_meta( $user_id, 'hsf_saved_filters', $filters );

    do_action( 'hsf_filter_saved', $user_id, $filter );

    return true;
}

/**
 * Delete a saved filter by ID.
 */
function hsf_delete_filter( $user_id, $filter_id ) {
    if ( ! $user_id ) {
        return false;
    }

    $filters = hsf_get_saved_filters( $user_id );

    $filters = array_filter(
        $filters,
        function ( $item ) use ( $filter_id ) {
            return isset( $item['id'] ) && $item['id'] !== $filter_id;
        }
    );

    $filters = apply_filters( 'hsf_pre_delete_filters', $filters, $user_id, $filter_id );

    update_user_meta( $user_id, 'hsf_saved_filters', $filters );

    do_action( 'hsf_filter_deleted', $user_id, $filter_id );

    return true;
} 