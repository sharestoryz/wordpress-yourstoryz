( function ( wp ) {
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var decodeEntities = wp.htmlEntities.decodeEntities;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var Placeholder = wp.components.Placeholder;
	var ComboboxControl = wp.components.ComboboxControl;
	var ServerSideRender = wp.serverSideRender;
	var REST_BASE = '/wp/v2/yourstoryz_story';

	function toOption( story ) {
		return { value: story.id, label: decodeEntities( story.title.rendered ) };
	}

	function StoryPicker( props ) {
		var searchState = useState( '' );
		var search = searchState[ 0 ];
		var setSearch = searchState[ 1 ];
		var resultsState = useState( [] );
		var results = resultsState[ 0 ];
		var setResults = resultsState[ 1 ];
		var currentState = useState( null );
		var current = currentState[ 0 ];
		var setCurrent = currentState[ 1 ];

		useEffect( function () {
			var timer = setTimeout( function () {
				wp.apiFetch( {
					path:
						REST_BASE +
						'?search=' +
						encodeURIComponent( search ) +
						'&per_page=20&_fields=id,title&status=publish',
				} )
					.then( function ( stories ) {
						setResults( stories.map( toOption ) );
					} )
					.catch( function () {
						setResults( [] );
					} );
			}, 300 );

			return function () {
				clearTimeout( timer );
			};
		}, [ search ] );

		useEffect( function () {
			if ( ! props.value ) {
				setCurrent( null );
				return;
			}

			wp.apiFetch( { path: REST_BASE + '/' + props.value + '?_fields=id,title' } )
				.then( function ( story ) {
					setCurrent( toOption( story ) );
				} )
				.catch( function () {
					setCurrent( null );
				} );
		}, [ props.value ] );

		var options = results.filter( function ( option ) {
			return ! current || option.value !== current.value;
		} );

		if ( current ) {
			options = [ current ].concat( options );
		}

		return el( ComboboxControl, {
			label: __( 'Story', 'yourstoryz' ),
			value: props.value || null,
			options: options,
			onFilterValueChange: setSearch,
			onChange: function ( value ) {
				props.onChange( value ? parseInt( value, 10 ) : 0 );
			},
		} );
	}

	wp.blocks.registerBlockType( 'yourstoryz/story', {
		edit: function ( props ) {
			var storyId = props.attributes.storyId;

			function setStoryId( value ) {
				props.setAttributes( { storyId: value } );
			}

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Instellingen', 'yourstoryz' ) },
						el( StoryPicker, { value: storyId, onChange: setStoryId } )
					)
				),
				el(
					'div',
					useBlockProps(),
					storyId
						? el( ServerSideRender, {
								block: 'yourstoryz/story',
								attributes: props.attributes,
						  } )
						: el(
								Placeholder,
								{
									icon: 'format-video',
									label: __( 'Story', 'yourstoryz' ),
									instructions: __( 'Kies een story', 'yourstoryz' ),
								},
								el( StoryPicker, { value: storyId, onChange: setStoryId } )
						  )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
