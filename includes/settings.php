<?php
// -----------------------------------------------------------------------------
// Register settings, sections, and fields
// -----------------------------------------------------------------------------

function hsf_settings_init() {
	// Register options.
	register_setting( 'hsf_settings', 'hsf_search_fields', 'hsf_sanitize_text' );
	register_setting( 'hsf_settings', 'hsf_sort_options', 'hsf_sanitize_text' );
	register_setting( 'hsf_settings', 'hsf_autocomplete', 'hsf_sanitize_checkbox' );
	register_setting( 'hsf_settings', 'hsf_results_per_page', 'hsf_sanitize_int' );
	register_setting( 'hsf_settings', 'hsf_ajax_search', 'hsf_sanitize_checkbox' );
	register_setting( 'hsf_settings', 'hsf_cache_duration', 'hsf_sanitize_int' );
	register_setting( 'hsf_settings', 'hsf_whatsapp_integration', 'hsf_sanitize_text' );

	// Main section
	add_settings_section(
		'hsf_main_section',
		__( 'Search & Filter Settings', 'happy-search-and-filter' ),
		'hsf_main_section_callback',
		'hsf_settings'
	);

	// Fields
	$fields = array(
		'hsf_search_fields'        => __( 'Search Fields', 'happy-search-and-filter' ),
		'hsf_sort_options'         => __( 'Sort Options', 'happy-search-and-filter' ),
		'hsf_autocomplete'         => __( 'Enable Autocomplete', 'happy-search-and-filter' ),
		'hsf_results_per_page'     => __( 'Results Per Page', 'happy-search-and-filter' ),
		'hsf_ajax_search'          => __( 'Enable AJAX Search', 'happy-search-and-filter' ),
		'hsf_cache_duration'       => __( 'Cache Duration (minutes)', 'happy-search-and-filter' ),
		'hsf_whatsapp_integration' => __( 'WhatsApp Integration', 'happy-search-and-filter' ),
	);

	foreach ( $fields as $field_id => $label ) {
		add_settings_field(
			$field_id,
			$label,
			'hsf_settings_field_callback',
			'hsf_settings',
			'hsf_main_section',
			array( 'id' => $field_id, 'label' => $label )
		);
	}
}
add_action( 'admin_init', 'hsf_settings_init' );

// Section description
function hsf_main_section_callback() {
	echo '<p>' . esc_html__( 'Configure default behaviour for the search/filter components.', 'happy-search-and-filter' ) . '</p>';
}

// Field callback – renders appropriate input based on id
function hsf_settings_field_callback( $args ) {
	$id    = $args['id'];
	$value = get_option( $id );

	switch ( $id ) {
		case 'hsf_autocomplete':
		case 'hsf_ajax_search':
			printf( '<input type="checkbox" id="%1$s" name="%1$s" value="on" %2$s />', esc_attr( $id ), checked( $value, 'on', false ) );
			break;
		case 'hsf_results_per_page':
		case 'hsf_cache_duration':
			printf( '<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text" />', esc_attr( $id ), esc_attr( $value ) );
			break;
		default:
			printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />', esc_attr( $id ), esc_attr( $value ) );
	}
}

// -----------------------------------------------------------------------------
// Add settings page to the Admin menu
// -----------------------------------------------------------------------------

function hsf_settings_menu() {
	add_options_page(
		__( 'Happy Search and Filter Settings', 'happy-search-and-filter' ),
		__( 'HSF Settings', 'happy-search-and-filter' ),
		'manage_options',
		'hsf-settings',
		'hsf_settings_page'
	);
}
add_action( 'admin_menu', 'hsf_settings_menu' );

// -----------------------------------------------------------------------------
// Render settings page
// -----------------------------------------------------------------------------

function hsf_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'Happy Search and Filter Settings', 'happy-search-and-filter' ); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'hsf_settings' );
            do_settings_sections( 'hsf_settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
?>
