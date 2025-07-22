<?php
/**
 * HSF Assets Management Class
 * 
 * Handles CSS and JS asset optimization, minification, and caching
 * 
 * @package HappySearchAndFilter
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSF_Assets {
    
    /**
     * Plugin version for cache busting
     */
    const VERSION = '1.1.0';
    
    /**
     * Asset cache directory
     */
    private static $cache_dir;
    
    /**
     * Initialize the assets manager
     */
    public static function init() {
        self::$cache_dir = WP_CONTENT_DIR . '/cache/hsf-assets/';
        
        // Create cache directory if it doesn't exist
        if (!file_exists(self::$cache_dir)) {
            wp_mkdir_p(self::$cache_dir);
            
            // Create .htaccess to prevent direct access
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents(self::$cache_dir . '.htaccess', $htaccess_content);
        }
        
        // Register hooks
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_optimized_assets'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        add_action('wp_head', array(__CLASS__, 'preload_critical_assets'));
        add_action('wp_footer', array(__CLASS__, 'defer_non_critical_assets'));
        
        // Clear cache on plugin updates
        add_action('upgrader_process_complete', array(__CLASS__, 'clear_asset_cache'));
        
        // Add rewrite rules for asset serving
        add_action('init', array(__CLASS__, 'add_rewrite_rules'));
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        add_action('template_redirect', array(__CLASS__, 'serve_optimized_assets'));
    }
    
    /**
     * Enqueue optimized frontend assets
     */
    public static function enqueue_optimized_assets() {
        $css_file = self::get_optimized_css_file();
        $js_file = self::get_optimized_js_file();
        
        // Enqueue optimized CSS
        if ($css_file) {
            wp_enqueue_style(
                'hsf-advanced-filter',
                $css_file,
                array(),
                self::VERSION,
                'all'
            );
        }
        
        // Enqueue optimized JS
        if ($js_file) {
            wp_enqueue_script(
                'hsf-advanced-filter',
                $js_file,
                array('jquery'),
                self::VERSION,
                true
            );
            
            // Localize script with AJAX data
            wp_localize_script('hsf-advanced-filter', 'hsf_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hsf_advanced_search_nonce'),
                'strings' => array(
                    'searching' => __('Searching...', 'happy-search-and-filter'),
                    'no_results' => __('No results found.', 'happy-search-and-filter'),
                    'error' => __('An error occurred. Please try again.', 'happy-search-and-filter'),
                    'loading' => __('Loading...', 'happy-search-and-filter'),
                    'load_more' => __('Load More', 'happy-search-and-filter'),
                    'no_more_results' => __('No more results to load.', 'happy-search-and-filter')
                )
            ));
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'happy-search-and-filter') !== false) {
            wp_enqueue_style(
                'hsf-admin',
                plugin_dir_url(__FILE__) . '../assets/css/admin.css',
                array(),
                self::VERSION
            );
            
            wp_enqueue_script(
                'hsf-admin',
                plugin_dir_url(__FILE__) . '../assets/js/admin.js',
                array('jquery'),
                self::VERSION,
                true
            );
        }
    }
    
    /**
     * Preload critical assets
     */
    public static function preload_critical_assets() {
        $css_file = self::get_optimized_css_file();
        if ($css_file) {
            echo '<link rel="preload" href="' . esc_url($css_file) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
            echo '<noscript><link rel="stylesheet" href="' . esc_url($css_file) . '"></noscript>';
        }
    }
    
    /**
     * Defer non-critical assets
     */
    public static function defer_non_critical_assets() {
        $js_file = self::get_optimized_js_file();
        if ($js_file) {
            echo '<script>window.addEventListener("load", function() {';
            echo 'var script = document.createElement("script");';
            echo 'script.src = "' . esc_url($js_file) . '";';
            echo 'script.async = true;';
            echo 'document.head.appendChild(script);';
            echo '});</script>';
        }
    }
    
    /**
     * Get optimized CSS file path
     */
    public static function get_optimized_css_file() {
        $cache_file = self::$cache_dir . 'advanced-filter.min.css';
        $source_file = plugin_dir_path(__FILE__) . '../assets/css/advanced-filter.css';
        
        // Check if cache exists and is fresh
        if (file_exists($cache_file) && filemtime($cache_file) > filemtime($source_file)) {
            return content_url('cache/hsf-assets/advanced-filter.min.css');
        }
        
        // Generate optimized CSS
        if (self::minify_css($source_file, $cache_file)) {
            return content_url('cache/hsf-assets/advanced-filter.min.css');
        }
        
        // Fallback to original file
        return plugin_dir_url(__FILE__) . '../assets/css/advanced-filter.css';
    }
    
    /**
     * Get optimized JS file path
     */
    private static function get_optimized_js_file() {
        $cache_file = self::$cache_dir . 'advanced-filter.min.js';
        $source_file = plugin_dir_path(__FILE__) . '../assets/js/advanced-filter.js';
        
        // Check if cache exists and is fresh
        if (file_exists($cache_file) && filemtime($cache_file) > filemtime($source_file)) {
            return content_url('cache/hsf-assets/advanced-filter.min.js');
        }
        
        // Generate optimized JS
        if (self::minify_js($source_file, $cache_file)) {
            return content_url('cache/hsf-assets/advanced-filter.min.js');
        }
        
        // Fallback to original file
        return plugin_dir_url(__FILE__) . '../assets/js/advanced-filter.js';
    }
    
    /**
     * Minify CSS file
     */
    public static function minify_css($source_file, $cache_file) {
        if (!file_exists($source_file)) {
            return false;
        }
        
        $css_content = file_get_contents($source_file);
        if ($css_content === false) {
            return false;
        }
        
        // Basic CSS minification
        $minified = self::minify_css_content($css_content);
        
        // Write to cache file
        return file_put_contents($cache_file, $minified) !== false;
    }
    
    /**
     * Minify CSS content
     */
    public static function minify_css_content($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/;\s*/', ';', $css);
        $css = preg_replace('/:\s*/', ':', $css);
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/\s*}\s*/', '}', $css);
        $css = preg_replace('/;\s*}/', '}', $css);
        
        // Remove leading/trailing whitespace
        $css = trim($css);
        
        return $css;
    }
    
    /**
     * Minify JS file
     */
    public static function minify_js($source_file, $cache_file) {
        if (!file_exists($source_file)) {
            return false;
        }
        
        $js_content = file_get_contents($source_file);
        if ($js_content === false) {
            return false;
        }
        
        // Basic JS minification
        $minified = self::minify_js_content($js_content);
        
        // Write to cache file
        return file_put_contents($cache_file, $minified) !== false;
    }
    
    /**
     * Minify JS content
     */
    public static function minify_js_content($js) {
        // Remove single-line comments (but preserve URLs)
        $js = preg_replace('/(?<!:)\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        $js = preg_replace('/;\s*/', ';', $js);
        $js = preg_replace('/{\s*/', '{', $js);
        $js = preg_replace('/\s*}/', '}', $js);
        $js = preg_replace('/,\s*/', ',', $js);
        $js = preg_replace('/:\s*/', ':', $js);
        $js = preg_replace('/=\s*/', '=', $js);
        $js = preg_replace('/\?\s*/', '?', $js);
        $js = preg_replace('/:\s*/', ':', $js);
        $js = preg_replace('/\|\|\s*/', '||', $js);
        $js = preg_replace('/&&\s*/', '&&', $js);
        
        // Remove leading/trailing whitespace
        $js = trim($js);
        
        return $js;
    }
    
    /**
     * Clear asset cache
     */
    public static function clear_asset_cache() {
        $files = glob(self::$cache_dir . '*.{css,js}', GLOB_BRACE);
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Add rewrite rules for asset serving
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule(
            '^hsf-assets/(.+)$',
            'index.php?hsf_asset=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public static function add_query_vars($vars) {
        $vars[] = 'hsf_asset';
        return $vars;
    }
    
    /**
     * Serve optimized assets
     */
    public static function serve_optimized_assets() {
        $asset = get_query_var('hsf_asset');
        if (!$asset) {
            return;
        }
        
        $asset_path = self::$cache_dir . $asset;
        if (!file_exists($asset_path)) {
            wp_die('Asset not found', 404);
        }
        
        // Set appropriate headers
        $ext = pathinfo($asset, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            default:
                wp_die('Invalid asset type', 400);
        }
        
        // Set cache headers
        header('Cache-Control: public, max-age=31536000'); // 1 year
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', filemtime($asset_path)));
        
        // Output file content
        readfile($asset_path);
        exit;
    }
    
    /**
     * Get asset optimization stats
     */
    public static function get_optimization_stats() {
        $stats = array(
            'css_original_size' => 0,
            'css_optimized_size' => 0,
            'js_original_size' => 0,
            'js_optimized_size' => 0,
            'savings_percentage' => 0
        );
        
        $css_source = plugin_dir_path(__FILE__) . '../assets/css/advanced-filter.css';
        $js_source = plugin_dir_path(__FILE__) . '../assets/js/advanced-filter.js';
        $css_cache = self::$cache_dir . 'advanced-filter.min.css';
        $js_cache = self::$cache_dir . 'advanced-filter.min.js';
        
        if (file_exists($css_source)) {
            $stats['css_original_size'] = filesize($css_source);
        }
        if (file_exists($css_cache)) {
            $stats['css_optimized_size'] = filesize($css_cache);
        }
        if (file_exists($js_source)) {
            $stats['js_original_size'] = filesize($js_source);
        }
        if (file_exists($js_cache)) {
            $stats['js_optimized_size'] = filesize($js_cache);
        }
        
        $total_original = $stats['css_original_size'] + $stats['js_original_size'];
        $total_optimized = $stats['css_optimized_size'] + $stats['js_optimized_size'];
        
        if ($total_original > 0) {
            $stats['savings_percentage'] = round((($total_original - $total_optimized) / $total_original) * 100, 2);
        }
        
        return $stats;
    }
}

// Initialize the assets manager
HSF_Assets::init(); 