(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { ToggleControl, PanelBody, SelectControl, TextControl } = wp.components;
    const { InspectorControls } = wp.blockEditor || wp.editor; // Support both old and new
    const { createElement: el, Fragment } = wp.element;

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
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const toggle = function(field) {
                return function(value) {
                    const newAttributes = {};
                    newAttributes[field] = value;
                    setAttributes(newAttributes);
                };
            };

            const filterOptions = [
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
            ];

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: 'General Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'Title',
                            value: attributes.title,
                            onChange: function(value) { setAttributes({ title: value }); }
                        }),
                        el(SelectControl, {
                            label: 'Layout',
                            value: attributes.layout,
                            options: [
                                { label: 'Horizontal', value: 'horizontal' },
                                { label: 'Vertical', value: 'vertical' },
                                { label: 'Grid', value: 'grid' },
                                { label: 'Compact', value: 'compact' }
                            ],
                            onChange: function(value) { setAttributes({ layout: value }); }
                        }),
                        el(SelectControl, {
                            label: 'Filter Style',
                            value: attributes.filterStyle,
                            options: [
                                { label: 'Standard', value: 'standard' },
                                { label: 'Minimal', value: 'minimal' },
                                { label: 'Boxed', value: 'boxed' },
                                { label: 'Modern', value: 'modern' }
                            ],
                            onChange: function(value) { setAttributes({ filterStyle: value }); }
                        }),
                        el(TextControl, {
                            label: 'Results Per Page',
                            type: 'number',
                            value: attributes.resultsPerPage,
                            onChange: function(value) { 
                                setAttributes({ resultsPerPage: parseInt(value, 10) || 10 }); 
                            }
                        })
                    ),
                    el(
                        PanelBody,
                        { title: 'Filter Options', initialOpen: false },
                        filterOptions.map(function(option) {
                            return el(ToggleControl, {
                                key: option.field,
                                label: option.label,
                                checked: attributes[option.field],
                                onChange: toggle(option.field)
                            });
                        })
                    ),
                    el(
                        PanelBody,
                        { title: 'Advanced Options', initialOpen: false },
                        el(ToggleControl, {
                            label: 'Enable Auto Submit',
                            checked: attributes.enableAutoSubmit,
                            onChange: toggle('enableAutoSubmit')
                        }),
                        el(ToggleControl, {
                            label: 'Enable Saved Filters',
                            checked: attributes.enableSavedFilters,
                            onChange: toggle('enableSavedFilters')
                        }),
                        el(ToggleControl, {
                            label: 'Show Filter Toggle (Mobile)',
                            checked: attributes.showFilterToggle,
                            onChange: toggle('showFilterToggle')
                        }),
                        el(TextControl, {
                            label: 'Distance Options (comma-separated)',
                            value: attributes.maxDistanceOptions,
                            onChange: function(value) { setAttributes({ maxDistanceOptions: value }); }
                        }),
                        el(SelectControl, {
                            label: 'Default Distance Unit',
                            value: attributes.defaultDistanceUnit,
                            options: [
                                { label: 'Kilometers', value: 'km' },
                                { label: 'Miles', value: 'mi' }
                            ],
                            onChange: function(value) { setAttributes({ defaultDistanceUnit: value }); }
                        })
                    )
                ),
                el(
                    'div',
                    { 
                        style: { 
                            padding: '20px', 
                            border: '1px solid #ddd', 
                            borderRadius: '4px', 
                            backgroundColor: '#f9f9f9' 
                        }
                    },
                    el('h3', { style: { margin: '0 0 10px 0' } }, attributes.title || 'Advanced Search Filters'),
                    el(
                        'p',
                        { style: { margin: '0', color: '#666' } },
                        el('strong', {}, 'Layout: '),
                        attributes.layout,
                        ' | ',
                        el('strong', {}, 'Style: '),
                        attributes.filterStyle,
                        ' | ',
                        el('strong', {}, 'Results: '),
                        attributes.resultsPerPage + ' per page'
                    ),
                    el(
                        'p',
                        { style: { margin: '10px 0 0 0', fontSize: '12px', color: '#999' } },
                        'Configure the filter options in the sidebar. This block will render the advanced search form on the frontend.'
                    )
                )
            );
        },
        save: function() {
            return null; // Rendered via PHP
        }
    });
})(window.wp);
