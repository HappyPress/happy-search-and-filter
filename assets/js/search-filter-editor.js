const { registerBlockType } = wp.blocks;
const { TextControl, ToggleControl, PanelBody, PanelRow } = wp.components;
const { InspectorControls } = wp.blockEditor;

registerBlockType('hsf/search-filter', {
    title: 'Search and Filter',
    icon: 'search',
    category: 'widgets',
    attributes: {
        keyword: { type: 'string' },
        category: { type: 'string' },
        location: { type: 'string' },
        date_range: { type: 'string' },
        autocomplete: { type: 'boolean', default: false }
    },
    edit: ({ attributes, setAttributes }) => {
        const { keyword, category, location, date_range, autocomplete } = attributes;

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Search and Filter Settings">
                        <PanelRow>
                            <ToggleControl
                                label="Enable Autocomplete"
                                checked={autocomplete}
                                onChange={(value) => setAttributes({ autocomplete: value })}
                            />
                        </PanelRow>
                    </PanelBody>
                </InspectorControls>
                <div>
                    <TextControl
                        label="Keyword"
                        value={keyword}
                        onChange={(value) => setAttributes({ keyword: value })}
                    />
                    <TextControl
                        label="Category"
                        value={category}
                        onChange={(value) => setAttributes({ category: value })}
                    />
                    <TextControl
                        label="Location"
                        value={location}
                        onChange={(value) => setAttributes({ location: value })}
                    />
                    <TextControl
                        label="Date Range"
                        value={date_range}
                        onChange={(value) => setAttributes({ date_range: value })}
                    />
                </div>
            </>
        );
    },
    save: ({ attributes }) => {
        return null; // Rendered with PHP
    }
});
