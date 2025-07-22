<?php
/**
 * Happy Search & Filter - Caching System
 * Handles caching of search results for improved performance
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache class for managing search results
 */
class HSF_Cache {
    
    /**
     * Cache prefix for all HSF cache keys
     */
    const CACHE_PREFIX = 'hsf_search_';
    
    /**
     * Default cache expiration time (1 hour)
     */
    const DEFAULT_EXPIRATION = 3600;
    
    /**
     * Maximum cache expiration time (24 hours)
     */
    const MAX_EXPIRATION = 86400;
    
    /**
     * Generate cache key from search parameters
     */
    public static function generate_cache_key($params) {
        // Remove non-essential parameters
        $cache_params = array_diff_key($params, array_flip([
            'action', 'nonce', 'paged' // These don't affect the actual search
        ]));
        
        // Sort parameters for consistent cache keys
        ksort($cache_params);
        
        // Create hash from parameters
        $hash = md5(serialize($cache_params));
        
        return self::CACHE_PREFIX . $hash;
    }
    
    /**
     * Get cached search results
     */
    public static function get_cached_results($params) {
        $cache_key = self::generate_cache_key($params);
        
        // Try to get from cache
        $cached_data = wp_cache_get($cache_key, 'hsf_search');
        
        if ($cached_data === false) {
            // Try WordPress transients as fallback
            $cached_data = get_transient($cache_key);
        }
        
        if ($cached_data !== false) {
            // Update cache timestamp for LRU behavior
            self::update_cache_timestamp($cache_key);
            return $cached_data;
        }
        
        return false;
    }
    
    /**
     * Cache search results
     */
    public static function cache_results($params, $results, $expiration = null) {
        if ($expiration === null) {
            $expiration = self::DEFAULT_EXPIRATION;
        }
        
        // Limit expiration time
        $expiration = min($expiration, self::MAX_EXPIRATION);
        
        $cache_key = self::generate_cache_key($params);
        $cache_data = array(
            'results' => $results,
            'timestamp' => time(),
            'expiration' => $expiration,
            'params' => $params
        );
        
        // Try object cache first
        $cached = wp_cache_set($cache_key, $cache_data, 'hsf_search', $expiration);
        
        // Fallback to WordPress transients
        if (!$cached) {
            set_transient($cache_key, $cache_data, $expiration);
        }
        
        // Store cache key for cleanup
        self::store_cache_key($cache_key);
        
        return $cached;
    }
    
    /**
     * Update cache timestamp for LRU behavior
     */
    private static function update_cache_timestamp($cache_key) {
        $cached_data = wp_cache_get($cache_key, 'hsf_search');
        
        if ($cached_data === false) {
            $cached_data = get_transient($cache_key);
        }
        
        if ($cached_data !== false) {
            $cached_data['last_accessed'] = time();
            
            wp_cache_set($cache_key, $cached_data, 'hsf_search', $cached_data['expiration']);
            set_transient($cache_key, $cached_data, $cached_data['expiration']);
        }
    }
    
    /**
     * Store cache key for cleanup operations
     */
    private static function store_cache_key($cache_key) {
        $cache_keys = get_option('hsf_cache_keys', array());
        $cache_keys[] = $cache_key;
        
        // Keep only last 1000 cache keys
        if (count($cache_keys) > 1000) {
            $cache_keys = array_slice($cache_keys, -1000);
        }
        
        update_option('hsf_cache_keys', $cache_keys);
    }
    
    /**
     * Clear all cached search results
     */
    public static function clear_all_cache() {
        $cache_keys = get_option('hsf_cache_keys', array());
        
        foreach ($cache_keys as $cache_key) {
            wp_cache_delete($cache_key, 'hsf_search');
            delete_transient($cache_key);
        }
        
        delete_option('hsf_cache_keys');
        
        return true;
    }
    
    /**
     * Clear expired cache entries
     */
    public static function clear_expired_cache() {
        $cache_keys = get_option('hsf_cache_keys', array());
        $valid_keys = array();
        
        foreach ($cache_keys as $cache_key) {
            $cached_data = wp_cache_get($cache_key, 'hsf_search');
            
            if ($cached_data === false) {
                $cached_data = get_transient($cache_key);
            }
            
            if ($cached_data !== false) {
                $expiration_time = $cached_data['timestamp'] + $cached_data['expiration'];
                
                if (time() < $expiration_time) {
                    $valid_keys[] = $cache_key;
                } else {
                    // Remove expired cache
                    wp_cache_delete($cache_key, 'hsf_search');
                    delete_transient($cache_key);
                }
            }
        }
        
        update_option('hsf_cache_keys', $valid_keys);
        
        return count($cache_keys) - count($valid_keys);
    }
    
    /**
     * Get cache statistics
     */
    public static function get_cache_stats() {
        $cache_keys = get_option('hsf_cache_keys', array());
        $stats = array(
            'total_entries' => count($cache_keys),
            'total_size' => 0,
            'expired_entries' => 0,
            'valid_entries' => 0
        );
        
        foreach ($cache_keys as $cache_key) {
            $cached_data = wp_cache_get($cache_key, 'hsf_search');
            
            if ($cached_data === false) {
                $cached_data = get_transient($cache_key);
            }
            
            if ($cached_data !== false) {
                $expiration_time = $cached_data['timestamp'] + $cached_data['expiration'];
                
                if (time() < $expiration_time) {
                    $stats['valid_entries']++;
                    $stats['total_size'] += strlen(serialize($cached_data));
                } else {
                    $stats['expired_entries']++;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Invalidate cache when business listings are updated
     */
    public static function invalidate_cache_on_update($post_id, $post, $update) {
        // Only invalidate for business_listing post type
        if ($post->post_type !== 'business_listing') {
            return;
        }
        
        // Clear all search cache when a business is updated
        self::clear_all_cache();
    }
    
    /**
     * Invalidate cache when terms are updated
     */
    public static function invalidate_cache_on_term_update($term_id, $tt_id, $taxonomy) {
        // Only invalidate for business-related taxonomies
        if (in_array($taxonomy, array('business_category', 'business_tag'))) {
            self::clear_all_cache();
        }
    }
    
    /**
     * Get cache expiration based on search complexity
     */
    public static function get_cache_expiration($params) {
        $base_expiration = self::DEFAULT_EXPIRATION;
        
        // Reduce cache time for complex searches
        if (!empty($params['search'])) {
            $base_expiration = min($base_expiration, 1800); // 30 minutes for text searches
        }
        
        // Reduce cache time for filtered searches
        $filter_count = 0;
        $filter_fields = array('category', 'location', 'company_type', 'rating_min', 'price_range', 'verified');
        
        foreach ($filter_fields as $field) {
            if (!empty($params[$field])) {
                $filter_count++;
            }
        }
        
        if ($filter_count > 2) {
            $base_expiration = min($base_expiration, 900); // 15 minutes for heavily filtered searches
        }
        
        return $base_expiration;
    }
    
    /**
     * Check if caching is enabled
     */
    public static function is_caching_enabled() {
        // Check if caching is disabled via constant
        if (defined('HSF_DISABLE_CACHE') && HSF_DISABLE_CACHE) {
            return false;
        }
        
        // Check if caching is disabled via option
        if (get_option('hsf_disable_cache', false)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get cache hit rate
     */
    public static function get_cache_hit_rate() {
        $stats = get_option('hsf_cache_stats', array(
            'hits' => 0,
            'misses' => 0
        ));
        
        $total = $stats['hits'] + $stats['misses'];
        
        if ($total === 0) {
            return 0;
        }
        
        return round(($stats['hits'] / $total) * 100, 2);
    }
    
    /**
     * Update cache statistics
     */
    public static function update_cache_stats($hit = true) {
        $stats = get_option('hsf_cache_stats', array(
            'hits' => 0,
            'misses' => 0
        ));
        
        if ($hit) {
            $stats['hits']++;
        } else {
            $stats['misses']++;
        }
        
        update_option('hsf_cache_stats', $stats);
    }
}

// Hook into WordPress events to invalidate cache
add_action('save_post', array('HSF_Cache', 'invalidate_cache_on_update'), 10, 3);
add_action('edited_term', array('HSF_Cache', 'invalidate_cache_on_term_update'), 10, 3);
add_action('delete_term', array('HSF_Cache', 'invalidate_cache_on_term_update'), 10, 3);

// Schedule cache cleanup
if (!wp_next_scheduled('hsf_cache_cleanup')) {
    wp_schedule_event(time(), 'hourly', 'hsf_cache_cleanup');
}

add_action('hsf_cache_cleanup', array('HSF_Cache', 'clear_expired_cache'));

// Admin actions for cache management
add_action('wp_ajax_hsf_clear_cache', 'hsf_ajax_clear_cache');
add_action('wp_ajax_hsf_get_cache_stats', 'hsf_ajax_get_cache_stats');

/**
 * AJAX handler for clearing cache
 */
function hsf_ajax_clear_cache() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Verify nonce
    if (!check_ajax_referer('hsf_cache_nonce', 'nonce', false)) {
        wp_send_json_error('Security check failed');
    }
    
    $cleared = HSF_Cache::clear_all_cache();
    
    if ($cleared) {
        wp_send_json_success('Cache cleared successfully');
    } else {
        wp_send_json_error('Failed to clear cache');
    }
}

/**
 * AJAX handler for getting cache statistics
 */
function hsf_ajax_get_cache_stats() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Verify nonce
    if (!check_ajax_referer('hsf_cache_nonce', 'nonce', false)) {
        wp_send_json_error('Security check failed');
    }
    
    $stats = HSF_Cache::get_cache_stats();
    $hit_rate = HSF_Cache::get_cache_hit_rate();
    
    wp_send_json_success(array(
        'stats' => $stats,
        'hit_rate' => $hit_rate
    ));
}
?> 