<?php
/**
 * HSF Critical CSS Class
 * 
 * Handles critical CSS extraction and inlining for above-the-fold content
 * 
 * @package HappySearchAndFilter
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class HSF_Critical_CSS {
    
    /**
     * Critical CSS cache directory
     */
    private static $cache_dir;
    
    /**
     * Initialize critical CSS
     */
    public static function init() {
        self::$cache_dir = WP_CONTENT_DIR . '/cache/hsf-critical/';
        
        // Create cache directory if it doesn't exist
        if (!file_exists(self::$cache_dir)) {
            wp_mkdir_p(self::$cache_dir);
            
            // Create .htaccess to prevent direct access
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents(self::$cache_dir . '.htaccess', $htaccess_content);
        }
        
        // Add hooks for critical CSS
        add_action('wp_head', array(__CLASS__, 'inline_critical_css'), 1);
        add_action('wp_footer', array(__CLASS__, 'load_non_critical_css'));
        
        // Clear cache on plugin updates
        add_action('upgrader_process_complete', array(__CLASS__, 'clear_critical_cache'));
    }
    
    /**
     * Inline critical CSS in head
     */
    public static function inline_critical_css() {
        $settings = get_option('hsf_performance_settings', array());
        
        if (!isset($settings['enable_critical_css']) || !$settings['enable_critical_css']) {
            return;
        }
        
        $critical_css = self::get_critical_css();
        if ($critical_css) {
            echo '<style id="hsf-critical-css">' . $critical_css . '</style>';
        }
    }
    
    /**
     * Load non-critical CSS
     */
    public static function load_non_critical_css() {
        $settings = get_option('hsf_performance_settings', array());
        
        if (!isset($settings['enable_critical_css']) || !$settings['enable_critical_css']) {
            return;
        }
        
        $css_file = HSF_Assets::get_optimized_css_file();
        if ($css_file) {
            echo '<link rel="preload" href="' . esc_url($css_file) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
            echo '<noscript><link rel="stylesheet" href="' . esc_url($css_file) . '"></noscript>';
        }
    }
    
    /**
     * Get critical CSS content
     */
    private static function get_critical_css() {
        $cache_file = self::$cache_dir . 'critical.css';
        $source_file = plugin_dir_path(__FILE__) . '../assets/css/advanced-filter.css';
        
        // Check if cache exists and is fresh
        if (file_exists($cache_file) && filemtime($cache_file) > filemtime($source_file)) {
            return file_get_contents($cache_file);
        }
        
        // Generate critical CSS
        if (self::generate_critical_css($source_file, $cache_file)) {
            return file_get_contents($cache_file);
        }
        
        return false;
    }
    
    /**
     * Generate critical CSS
     */
    public static function generate_critical_css($source_file, $cache_file) {
        if (!file_exists($source_file)) {
            return false;
        }
        
        $css_content = file_get_contents($source_file);
        if ($css_content === false) {
            return false;
        }
        
        // Extract critical CSS rules
        $critical_css = self::extract_critical_rules($css_content);
        
        // Write to cache file
        return file_put_contents($cache_file, $critical_css) !== false;
    }
    
    /**
     * Extract critical CSS rules
     */
    private static function extract_critical_rules($css_content) {
        // Define critical selectors (above-the-fold content)
        $critical_selectors = array(
            // Form elements
            '.hsf-advanced-filter',
            '.hsf-advanced-filter *',
            '.hbl-filter-form',
            '.hbl-filter-form *',
            '.hbl-filter-field',
            '.hbl-filter-field *',
            '.hbl-filter-actions',
            '.hbl-filter-actions *',
            '.hbl-search-button',
            '.hbl-reset-button',
            '.hbl-filter-toggle',
            
            // Search results container
            '.hbl-search-results',
            '.hbl-results-container',
            '.hbl-loading-indicator',
            '.hbl-no-results',
            
            // Basic layout
            '.hbl-filter-layout',
            '.hbl-filter-layout *',
            '.hbl-filter-row',
            '.hbl-filter-row *',
            '.hbl-filter-column',
            '.hbl-filter-column *',
            
            // Essential styling
            ':root',
            'body',
            'html',
            'input',
            'select',
            'button',
            'label',
            
            // Critical animations
            '@keyframes hsf-spin',
            '@keyframes hsf-shimmer',
            '.hbl-loading',
            '.hbl-spinner',
            '.hbl-shimmer'
        );
        
        // Extract CSS rules for critical selectors
        $critical_css = '';
        $lines = explode("\n", $css_content);
        $in_critical_rule = false;
        $brace_count = 0;
        $current_rule = '';
        
        foreach ($lines as $line) {
            $trimmed_line = trim($line);
            
            // Skip comments and empty lines
            if (empty($trimmed_line) || strpos($trimmed_line, '/*') === 0) {
                continue;
            }
            
            // Check if line contains critical selector
            $is_critical = false;
            foreach ($critical_selectors as $selector) {
                if (strpos($trimmed_line, $selector) !== false) {
                    $is_critical = true;
                    break;
                }
            }
            
            // Check for @keyframes for critical animations
            if (strpos($trimmed_line, '@keyframes') !== false) {
                foreach ($critical_selectors as $selector) {
                    if (strpos($trimmed_line, $selector) !== false) {
                        $is_critical = true;
                        break;
                    }
                }
            }
            
            if ($is_critical) {
                $in_critical_rule = true;
                $current_rule = $trimmed_line;
                $brace_count = substr_count($trimmed_line, '{') - substr_count($trimmed_line, '}');
            } elseif ($in_critical_rule) {
                $current_rule .= "\n" . $trimmed_line;
                $brace_count += substr_count($trimmed_line, '{') - substr_count($trimmed_line, '}');
                
                // If we've closed all braces, end the rule
                if ($brace_count <= 0) {
                    $critical_css .= $current_rule . "\n\n";
                    $in_critical_rule = false;
                    $current_rule = '';
                    $brace_count = 0;
                }
            }
        }
        
        // Add essential CSS variables and base styles
        $essential_css = self::get_essential_css();
        $critical_css = $essential_css . "\n\n" . $critical_css;
        
        return trim($critical_css);
    }
    
    /**
     * Get essential CSS (variables, base styles)
     */
    private static function get_essential_css() {
        return '
/* Essential CSS Variables */
:root {
    --hsf-primary-color: #667eea;
    --hsf-secondary-color: #764ba2;
    --hsf-background-color: #ffffff;
    --hsf-border-color: #e2e8f0;
    --hsf-text-color: #2d3748;
    --hsf-spacing-xs: 0.25rem;
    --hsf-spacing-sm: 0.5rem;
    --hsf-spacing-md: 1rem;
    --hsf-spacing-lg: 1.5rem;
    --hsf-border-radius: 0.375rem;
    --hsf-transition: all 0.2s ease-in-out;
}

/* Essential Base Styles */
.hsf-advanced-filter {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.5;
    color: var(--hsf-text-color);
}

.hbl-filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: var(--hsf-spacing-md);
    align-items: flex-end;
    padding: var(--hsf-spacing-lg);
    background: var(--hsf-background-color);
    border: 1px solid var(--hsf-border-color);
    border-radius: var(--hsf-border-radius);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.hbl-filter-field {
    flex: 1;
    min-width: 200px;
}

.hbl-filter-field label {
    display: block;
    margin-bottom: var(--hsf-spacing-xs);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--hsf-text-color);
}

.hbl-filter-field input,
.hbl-filter-field select {
    width: 100%;
    padding: var(--hsf-spacing-sm);
    border: 1px solid var(--hsf-border-color);
    border-radius: var(--hsf-border-radius);
    font-size: 0.875rem;
    transition: var(--hsf-transition);
}

.hbl-filter-actions {
    display: flex;
    gap: var(--hsf-spacing-sm);
    align-items: center;
}

.hbl-search-button,
.hbl-reset-button {
    padding: var(--hsf-spacing-sm) var(--hsf-spacing-md);
    border: none;
    border-radius: var(--hsf-border-radius);
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: var(--hsf-transition);
}

.hbl-search-button {
    background: linear-gradient(135deg, var(--hsf-primary-color), var(--hsf-secondary-color));
    color: white;
}

.hbl-reset-button {
    background: #f7fafc;
    color: var(--hsf-text-color);
    border: 1px solid var(--hsf-border-color);
}

/* Loading States */
.hbl-loading-indicator {
    display: none;
    text-align: center;
    padding: var(--hsf-spacing-lg);
}

.hbl-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid var(--hsf-border-color);
    border-top: 2px solid var(--hsf-primary-color);
    border-radius: 50%;
    animation: hsf-spin 1s linear infinite;
}

@keyframes hsf-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .hbl-filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .hbl-filter-field {
        min-width: auto;
    }
    
    .hbl-filter-actions {
        justify-content: center;
    }
}';
    }
    
    /**
     * Clear critical CSS cache
     */
    public static function clear_critical_cache() {
        $files = glob(self::$cache_dir . '*.css');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Get critical CSS stats
     */
    public static function get_critical_stats() {
        $stats = array(
            'critical_css_size' => 0,
            'full_css_size' => 0,
            'savings_percentage' => 0
        );
        
        $critical_cache = self::$cache_dir . 'critical.css';
        $full_css = plugin_dir_path(__FILE__) . '../assets/css/advanced-filter.css';
        
        if (file_exists($critical_cache)) {
            $stats['critical_css_size'] = filesize($critical_cache);
        }
        if (file_exists($full_css)) {
            $stats['full_css_size'] = filesize($full_css);
        }
        
        if ($stats['full_css_size'] > 0) {
            $stats['savings_percentage'] = round((($stats['full_css_size'] - $stats['critical_css_size']) / $stats['full_css_size']) * 100, 2);
        }
        
        return $stats;
    }
}

// Initialize critical CSS
HSF_Critical_CSS::init(); 