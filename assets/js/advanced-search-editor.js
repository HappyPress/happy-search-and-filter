(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { ToggleControl, PanelBody, SelectControl, TextControl } = wp.components;
    const { InspectorControls } = wp.blockEditor || wp.editor;
    const { createElement: el, Fragment } = wp.element;
    const { __ } = wp.i18n;

    registerBlockType('happy-search-and-filter/business-search', {
        title: __('Business Search', 'happy-search-and-filter'),
        icon: 'search',
        category: 'happy-business-listing',
        description: __('Advanced search and filter interface for business listings.', 'happy-search-and-filter'),
        keywords: [
            __('search', 'happy-search-and-filter'),
            __('filter', 'happy-search-and-filter'),
            __('business', 'happy-search-and-filter')
        ],
        attributes: {
            showSearch: { type: 'boolean', default: true },
            showCategoryFilter: { type: 'boolean', default: true },
            showLocationFilter: { type: 'boolean', default: true },
            showRatingFilter: { type: 'boolean', default: true },
            showVerifiedFilter: { type: 'boolean', default: true },
            showSortOptions: { type: 'boolean', default: true },
            filterStyle: { type: 'string', default: 'horizontal' },
            savedFilterId: { type: 'number', default: 0 },
            className: { type: 'string' }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;

            const filterStyleOptions = [
                { label: __('Horizontal', 'happy-search-and-filter'), value: 'horizontal' },
                { label: __('Vertical', 'happy-search-and-filter'), value: 'vertical' },
                { label: __('Grid', 'happy-search-and-filter'), value: 'grid' },
                { label: __('Compact', 'happy-search-and-filter'), value: 'compact' }
            ];

            const savedFilterOptions = window.hsfBlockData && window.hsfBlockData.savedFilters 
                ? window.hsfBlockData.savedFilters 
                : [{ label: __('Custom Filter', 'happy-search-and-filter'), value: 0 }];

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __('Filter Settings', 'happy-search-and-filter'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Saved Filter', 'happy-search-and-filter'),
                            value: attributes.savedFilterId,
                            options: savedFilterOptions,
                            onChange: function(value) { setAttributes({ savedFilterId: parseInt(value) }); },
                            help: __('Select a saved filter or create a custom one.', 'happy-search-and-filter')
                        }),
                        el(SelectControl, {
                            label: __('Filter Style', 'happy-search-and-filter'),
                            value: attributes.filterStyle,
                            options: filterStyleOptions,
                            onChange: function(value) { setAttributes({ filterStyle: value }); }
                        })
                    ),
                    el(
                        PanelBody,
                        { title: __('Filter Components', 'happy-search-and-filter'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show Search Field', 'happy-search-and-filter'),
                            checked: attributes.showSearch,
                            onChange: function(value) { setAttributes({ showSearch: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Category Filter', 'happy-search-and-filter'),
                            checked: attributes.showCategoryFilter,
                            onChange: function(value) { setAttributes({ showCategoryFilter: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Location Filter', 'happy-search-and-filter'),
                            checked: attributes.showLocationFilter,
                            onChange: function(value) { setAttributes({ showLocationFilter: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Rating Filter', 'happy-search-and-filter'),
                            checked: attributes.showRatingFilter,
                            onChange: function(value) { setAttributes({ showRatingFilter: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Verified Filter', 'happy-search-and-filter'),
                            checked: attributes.showVerifiedFilter,
                            onChange: function(value) { setAttributes({ showVerifiedFilter: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Sort Options', 'happy-search-and-filter'),
                            checked: attributes.showSortOptions,
                            onChange: function(value) { setAttributes({ showSortOptions: value }); }
                        })
                    )
                ),
                el(
                    'div',
                    { 
                        className: 'hsf-advanced-search-preview',
                        style: { 
                            padding: '20px', 
                            border: '2px dashed #ddd', 
                            borderRadius: '8px',
                            backgroundColor: '#f9f9f9',
                            textAlign: 'center'
                        }
                    },
                    el('div', { 
                        style: { 
                            fontSize: '48px', 
                            marginBottom: '16px',
                            color: '#666'
                        } 
                    }, '🔍'),
                    el('h3', { 
                        style: { 
                            margin: '0 0 8px 0',
                            color: '#333'
                        } 
                    }, __('Advanced Search & Filter', 'happy-search-and-filter')),
                    el('p', { 
                        style: { 
                            margin: '0 0 16px 0',
                            color: '#666'
                        } 
                    }, __('This block will display advanced search and filter options on the frontend.', 'happy-search-and-filter')),
                    el('div', {
                        style: {
                            display: 'flex',
                            justifyContent: 'center',
                            gap: '20px',
                            fontSize: '14px',
                            color: '#888',
                            flexWrap: 'wrap'
                        }
                    },
                        el('span', {}, __('Style:', 'happy-search-and-filter') + ' ' + attributes.filterStyle),
                        attributes.savedFilterId > 0 && el('span', {}, __('Saved Filter', 'happy-search-and-filter')),
                        !attributes.savedFilterId && el('span', {}, __('Custom Filter', 'happy-search-and-filter'))
                    ),
                    el('div', {
                        style: {
                            marginTop: '16px',
                            display: 'flex',
                            justifyContent: 'center',
                            gap: '12px',
                            flexWrap: 'wrap'
                        }
                    },
                        attributes.showSearch && el('span', {
                            style: {
                                padding: '4px 8px',
                                backgroundColor: '#e3f2fd',
                                borderRadius: '4px',
                                fontSize: '12px',
                                color: '#1976d2'
                            }
                        }, __('Search', 'happy-search-and-filter')),
                        attributes.showCategoryFilter && el('span', {
                            style: {
                                padding: '4px 8px',
                                backgroundColor: '#e8f5e8',
                                borderRadius: '4px',
                                fontSize: '12px',
                                color: '#2d7d2d'
                            }
                        }, __('Categories', 'happy-search-and-filter')),
                        attributes.showLocationFilter && el('span', {
                            style: {
                                padding: '4px 8px',
                                backgroundColor: '#fff3e0',
                                borderRadius: '4px',
                                fontSize: '12px',
                                color: '#f57c00'
                            }
                        }, __('Location', 'happy-search-and-filter')),
                        attributes.showRatingFilter && el('span', {
                            style: {
                                padding: '4px 8px',
                                backgroundColor: '#fef3c7',
                                borderRadius: '4px',
                                fontSize: '12px',
                                color: '#92400e'
                            }
                        }, __('Rating', 'happy-search-and-filter')),
                        attributes.showSortOptions && el('span', {
                            style: {
                                padding: '4px 8px',
                                backgroundColor: '#f3e8ff',
                                borderRadius: '4px',
                                fontSize: '12px',
                                color: '#7c3aed'
                            }
                        }, __('Sort', 'happy-search-and-filter'))
                    )
                )
            );
        },
        save: function() {
            return null; // Rendered via PHP
        }
    });
})(window.wp); 