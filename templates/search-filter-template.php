<div class="hbl-advanced-filter hsf-filter-wrapper">
    <form id="hsf-search-form" class="hbl-advanced-filter-form" method="post" action="#">
        <input type="text" id="hsf_keyword" placeholder="<?php esc_attr_e( 'Keyword', 'happy-search-and-filter' ); ?>">
        <input type="text" id="hsf_category" placeholder="<?php esc_attr_e( 'Category', 'happy-search-and-filter' ); ?>">
        <input type="text" id="hsf_location" placeholder="<?php esc_attr_e( 'Location', 'happy-search-and-filter' ); ?>">
        <input type="text" id="hsf_date_range" placeholder="<?php esc_attr_e( 'Date Range', 'happy-search-and-filter' ); ?>">
        <button type="submit"><?php _e( 'Search', 'happy-search-and-filter' ); ?></button>
    </form>
</div>
<div id="hsf-search-results"></div>
