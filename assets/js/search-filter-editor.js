const { registerBlockType } = wp.blocks;
const { ToggleControl, PanelBody, SelectControl, TextControl } = wp.components;
const { InspectorControls } = wp.blockEditor;

registerBlockType('happy-search-and-filter/advanced-search', {
    title: 'Advanced Search Filters',
    icon: 'filter',
    category: 'widgets',
    attributes: {
        title: { type: 'string', default: 'Find Businesses' },
        layout: { type: 'string', default: 'horizontal' },
        showKeywordSearch: { type: 'boolean', default: true },
        showLocationFilter: { type: 'boolean', default: true },
        showCategoryFilter: { type: 'boolean', default: true },
        showCompanyTypeFilter: { type: 'boolean', default: true },
        showRatingFilter: { type: 'boolean', default: false },
        showPriceRangeFilter: { type: 'boolean', default: false },
        showVerifiedFilter: { type: 'boolean', default: false },
        showSorting: { type: 'boolean', default: true },
        resultsPerPage: { type: 'number', default: 10 },
        showDateRangeFilter: { type: 'boolean', default: false },
        showServiceFilter: { type: 'boolean', default: false },
        showDistanceFilter: { type: 'boolean', default: false },
        showTagsFilter: { type: 'boolean', default: false },
        showOpenNowFilter: { type: 'boolean', default: false },
        maxDistanceOptions: { type: 'string', default: '5,10,25,50,100' },
        defaultDistanceUnit: { type: 'string', default: 'km' },
        enableAutoSubmit: { type: 'boolean', default: true },
        enableSavedFilters: { type: 'boolean', default: false },
        filterStyle: { type: 'string', default: 'standard' },
        showFilterToggle: { type: 'boolean', default: false },
        className: { type: 'string' }
    },
    edit: ( { attributes, setAttributes } ) => {
        const toggle = ( field ) => ( value ) => setAttributes( { [ field ]: value } );

        return (
            <>
                <InspectorControls>
                    <PanelBody title="General Settings" initialOpen={ true }>
                        <TextControl
                            label="Title"
                            value={ attributes.title }
                            onChange={ ( value ) => setAttributes( { title: value } ) }
                        />
                        <SelectControl
                            label="Layout"
                            value={ attributes.layout }
                            options={ [
                                { label: 'Horizontal', value: 'horizontal' },
                                { label: 'Vertical', value: 'vertical' },
                                { label: 'Grid', value: 'grid' },
                                { label: 'Compact', value: 'compact' }
                            ] }
                            onChange={ ( value ) => setAttributes( { layout: value } ) }
                        />
                        <SelectControl
                            label="Filter Style"
                            value={ attributes.filterStyle }
                            options={ [
                                { label: 'Standard', value: 'standard' },
                                { label: 'Minimal', value: 'minimal' },
                                { label: 'Boxed', value: 'boxed' },
                                { label: 'Modern', value: 'modern' }
                            ] }
                            onChange={ ( value ) => setAttributes( { filterStyle: value } ) }
                        />
                        <TextControl
                            label="Results Per Page"
                            type="number"
                            value={ attributes.resultsPerPage }
                            onChange={ ( value ) => setAttributes( { resultsPerPage: parseInt( value, 10 ) || 10 } ) }
                        />
                    </PanelBody>
                    <PanelBody title="Filter Options" initialOpen={ false }>
                        { [
                            { field: 'showKeywordSearch', label: 'Keyword Search' },
                            { field: 'showLocationFilter', label: 'Location Filter' },
                            { field: 'showCategoryFilter', label: 'Category Filter' },
                            { field: 'showCompanyTypeFilter', label: 'Company Type Filter' },
                            { field: 'showRatingFilter', label: 'Rating Filter' },
                            { field: 'showPriceRangeFilter', label: 'Price Range Filter' },
                            { field: 'showVerifiedFilter', label: 'Verified Filter' },
                            { field: 'showDateRangeFilter', label: 'Date Range Filter' },
                            { field: 'showServiceFilter', label: 'Service Filter' },
                            { field: 'showDistanceFilter', label: 'Distance Filter' },
                            { field: 'showTagsFilter', label: 'Tags Filter' },
                            { field: 'showOpenNowFilter', label: 'Open Now Filter' },
                            { field: 'showSorting', label: 'Sorting Options' }
                        ].map( ( { field, label } ) => (
                            <ToggleControl
                                key={ field }
                                label={ label }
                                checked={ attributes[ field ] }
                                onChange={ toggle( field ) }
                            />
                        ) ) }
                    </PanelBody>
                    <PanelBody title="Advanced Options" initialOpen={ false }>
                        <ToggleControl
                            label="Enable Auto Submit"
                            checked={ attributes.enableAutoSubmit }
                            onChange={ toggle( 'enableAutoSubmit' ) }
                        />
                        <ToggleControl
                            label="Enable Saved Filters"
                            checked={ attributes.enableSavedFilters }
                            onChange={ toggle( 'enableSavedFilters' ) }
                        />
                        <ToggleControl
                            label="Show Filter Toggle (Mobile)"
                            checked={ attributes.showFilterToggle }
                            onChange={ toggle( 'showFilterToggle' ) }
                        />
                        <TextControl
                            label="Distance Options (comma-separated)"
                            value={ attributes.maxDistanceOptions }
                            onChange={ ( value ) => setAttributes( { maxDistanceOptions: value } ) }
                        />
                        <SelectControl
                            label="Default Distance Unit"
                            value={ attributes.defaultDistanceUnit }
                            options={ [
                                { label: 'Kilometers', value: 'km' },
                                { label: 'Miles', value: 'mi' }
                            ] }
                            onChange={ ( value ) => setAttributes( { defaultDistanceUnit: value } ) }
                        />
                    </PanelBody>
                </InspectorControls>
                <div style={{ padding: '20px', border: '1px solid #ddd', borderRadius: '4px', backgroundColor: '#f9f9f9' }}>
                    <h3 style={{ margin: '0 0 10px 0' }}>{ attributes.title || 'Advanced Search Filters' }</h3>
                    <p style={{ margin: '0', color: '#666' }}>
                        <strong>Layout:</strong> { attributes.layout } | 
                        <strong> Style:</strong> { attributes.filterStyle } | 
                        <strong> Results:</strong> { attributes.resultsPerPage } per page
                    </p>
                    <p style={{ margin: '10px 0 0 0', fontSize: '12px', color: '#999' }}>
                        Configure the filter options in the sidebar. This block will render the advanced search form on the frontend.
                    </p>
                </div>
            </>
        );
    },
    save() {
        return null; // Rendered via PHP
    }
});
