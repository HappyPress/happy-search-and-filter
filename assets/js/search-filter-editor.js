const { registerBlockType } = wp.blocks;
const { ToggleControl, PanelBody, SelectControl, TextControl } = wp.components;
const { InspectorControls } = wp.blockEditor;

registerBlockType('happy-search-and-filter/advanced-search', {
    title: 'Advanced Search Filters',
    icon: 'filter',
    category: 'widgets',
    attributes: {
        title: { type: 'string', default: 'Find Businesses' },
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
        enableSavedFilters: { type: 'boolean', default: false },
        filterStyle: { type: 'string', default: 'standard' }
    },
    edit: ( { attributes, setAttributes } ) => {
        const toggle = ( field ) => ( value ) => setAttributes( { [ field ]: value } );

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Toggle Filters" initialOpen={ true }>
                        { [
                            'showKeywordSearch',
                            'showLocationFilter',
                            'showCategoryFilter',
                            'showCompanyTypeFilter',
                            'showRatingFilter',
                            'showPriceRangeFilter',
                            'showVerifiedFilter',
                            'showDateRangeFilter',
                            'showServiceFilter',
                            'showDistanceFilter',
                            'showTagsFilter',
                            'showOpenNowFilter',
                            'enableSavedFilters'
                        ].map( ( field ) => (
                            <ToggleControl
                                key={ field }
                                label={ field }
                                checked={ attributes[ field ] }
                                onChange={ toggle( field ) }
                            />
                        ) ) }
                    </PanelBody>
                    <PanelBody title="Layout & Style" initialOpen={ false }>
                        <SelectControl
                            label="Filter Style"
                            value={ attributes.filterStyle }
                            options={ [
                                { label: 'Standard', value: 'standard' },
                                { label: 'Minimal', value: 'minimal' }
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
                </InspectorControls>
                <p><strong>Advanced Search Filters Block</strong><br/>Configure the options in the sidebar.</p>
            </>
        );
    },
    save() {
        return null; // Rendered via PHP
    }
});
