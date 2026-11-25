( function ( wp ) {
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var ServerSideRender = wp.serverSideRender;

	function useTags() {
		var state = useState( [] );
		var tags = state[ 0 ];
		var setTags = state[ 1 ];

		useEffect( function () {
			wp.apiFetch( { path: '/wp/v2/tags?per_page=100&_fields=id,slug,name' } )
				.then( setTags )
				.catch( function () {
					setTags( [] );
				} );
		}, [] );

		return tags;
	}

	wp.blocks.registerBlockType( 'yourstoryz/stories', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var tags = useTags();

			var tagOptions = [ { label: __( 'Alle tags', 'yourstoryz' ), value: '' } ].concat(
				tags.map( function ( tag ) {
					return { label: tag.name, value: tag.slug };
				} )
			);

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Instellingen', 'yourstoryz' ) },
						el( RangeControl, {
							label: __( 'Aantal stories', 'yourstoryz' ),
							value: attributes.count,
							min: 1,
							max: 24,
							onChange: function ( value ) {
								setAttributes( { count: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Kolommen', 'yourstoryz' ),
							value: attributes.columns,
							min: 1,
							max: 4,
							onChange: function ( value ) {
								setAttributes( { columns: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Tag', 'yourstoryz' ),
							value: attributes.tag,
							options: tagOptions,
							onChange: function ( value ) {
								setAttributes( { tag: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Link naar alle stories tonen', 'yourstoryz' ),
							checked: attributes.showArchiveLink,
							onChange: function ( value ) {
								setAttributes( { showArchiveLink: value } );
							},
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					el( ServerSideRender, {
						block: 'yourstoryz/stories',
						attributes: attributes,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
