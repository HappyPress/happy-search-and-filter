<?php
// Register settings
function hsf_register_settings() {
    register_setting('hsf_settings_group', 'hsf_search_fields');
    register_setting('hsf_settings_group', 'hsf_sort_options');
    register_setting('hsf_settings_group', 'hsf_autocomplete');
    register_setting('hsf_settings_group', 'hsf_results_per_page');
    register_setting('hsf_settings_group', 'hsf_ajax_search');
    register_setting('hsf_settings_group', 'hsf_cache_duration');
    register_setting('hsf_settings_group', 'hsf_whatsapp_integration');
}
add_action('admin_init', 'hsf_register_settings');

// Add settings page to the admin menu
function hsf_settings_menu() {
    add_options_page('Happy Search and Filter Settings', 'HSF Settings', 'manage_options', 'hsf-settings', 'hsf_settings_page');
}
add_action('admin_menu', 'hsf_settings_menu');

// Render the settings page
function hsf_settings_page() {
?>
    <div class="wrap">
        <h1>Happy Search and Filter Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('hsf_settings_group'); ?>
            <?php do_settings_sections('hsf_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Search Fields</th>
                    <td><input type="text" name="hsf_search_fields" value="<?php echo esc_attr(get_option('hsf_search_fields')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Sort Options</th>
                    <td><input type="text" name="hsf_sort_options" value="<?php echo esc_attr(get_option('hsf_sort_options')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Autocomplete</th>
                    <td><input type="checkbox" name="hsf_autocomplete" <?php checked(get_option('hsf_autocomplete'), 'on'); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Results Per Page</th>
                    <td><input type="number" name="hsf_results_per_page" value="<?php echo esc_attr(get_option('hsf_results_per_page')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">AJAX Search</th>
                    <td><input type="checkbox" name="hsf_ajax_search" <?php checked(get_option('hsf_ajax_search'), 'on'); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cache Duration</th>
                    <td><input type="number" name="hsf_cache_duration" value="<?php echo esc_attr(get_option('hsf_cache_duration')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">WhatsApp Integration</th>
                    <td><input type="text" name="hsf_whatsapp_integration" value="<?php echo esc_attr(get_option('hsf_whatsapp_integration')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}
?>
