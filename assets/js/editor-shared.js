/**
 * Shared editor code: the "Surface" panel for the Group block and the quote
 * band, plus Group variations in the inserter. The surfaces come from the
 * theme (PHP filter "rmd_cb_surfaces", passed in window.rmdContentBlocks);
 * without surfaces nothing is added.
 *
 * Plain script without a build step; uses the wp.* globals.
 */
( function ( wp, config ) {
	'use strict';

	const el = wp.element.createElement;
	const { Fragment } = wp.element;
	const { InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl, ToggleControl } = wp.components;
	const { createHigherOrderComponent } = wp.compose;
	const { __ } = wp.i18n;

	const surfaces = ( config && config.surfaces ) || {};
	const COLOURS = ( surfaces.surfaces || [] ).map( ( s ) => ( { value: s.class, label: s.label } ) );
	const MODIFIERS = ( surfaces.modifiers || [] ).map( ( m ) => ( { value: m.class, label: m.label, help: m.help } ) );
	const ALL = COLOURS.concat( MODIFIERS ).map( ( o ) => o.value );

	const tokens = ( className ) => ( className || '' ).split( /\s+/ ).filter( Boolean );

	function surfaceOf( className ) {
		let found = '';
		tokens( className ).forEach( ( t ) => {
			if ( COLOURS.some( ( c ) => c.value === t ) ) {
				found = t;
			}
		} );
		return found;
	}

	function modifiersOf( className ) {
		return MODIFIERS.map( ( m ) => m.value ).filter( ( m ) => tokens( className ).indexOf( m ) !== -1 );
	}

	// Rebuilds the class list: classes that are not surfaces stay untouched.
	function withSurface( className, colour, mods ) {
		const rest = tokens( className ).filter( ( t ) => ALL.indexOf( t ) === -1 );

		if ( colour ) {
			rest.push( colour );
			mods.forEach( ( m ) => rest.push( m ) );
		}

		return rest.join( ' ' ) || undefined;
	}

	/**
	 * The panel. Props: className, onChange( newClassName, colour ), allowNone, initialOpen.
	 */
	function Panel( props ) {
		const className = props.className || '';
		const colour = surfaceOf( className );
		const mods = modifiersOf( className );
		const options = props.allowNone ? [ { value: '', label: __( 'None', 'rmd-content-blocks' ) } ].concat( COLOURS ) : COLOURS;

		const apply = ( nextColour, nextMods ) => props.onChange( withSurface( className, nextColour, nextMods ), nextColour );

		return el(
			PanelBody,
			{ title: __( 'Surface', 'rmd-content-blocks' ), initialOpen: undefined === props.initialOpen ? !! colour : props.initialOpen },
			el( SelectControl, {
				label: __( 'Background', 'rmd-content-blocks' ),
				value: colour,
				options,
				help: colour
					? surfaces.helpOn || __( 'Full width; the content keeps the content width.', 'rmd-content-blocks' )
					: surfaces.helpOff || __( 'Colours the section and sets it to full width.', 'rmd-content-blocks' ),
				onChange: ( value ) => apply( value, value ? mods : [] ),
				__nextHasNoMarginBottom: true,
			} ),
			colour
				? MODIFIERS.map( ( m ) =>
					el( ToggleControl, {
						key: m.value,
						label: m.label,
						help: m.help,
						checked: mods.indexOf( m.value ) !== -1,
						onChange: ( on ) => {
							const next = mods.filter( ( x ) => x !== m.value );
							if ( on ) {
								next.push( m.value );
							}
							apply( colour, next );
						},
						__nextHasNoMarginBottom: true,
					} )
				)
				: null
		);
	}

	window.rmdContentBlocksSurfaces = {
		hasSurfaces: COLOURS.length > 0,
		quoteDefault: surfaces.quoteDefault || '',
		surfaceOf,
		Panel,
	};

	if ( ! COLOURS.length ) {
		return;
	}

	/* Group block --------------------------------------------------------- */

	const withSurfacePanel = createHigherOrderComponent( ( BlockEdit ) => ( props ) => {
		if ( props.name !== 'core/group' ) {
			return el( BlockEdit, props );
		}

		return el(
			Fragment,
			null,
			el( BlockEdit, props ),
			el(
				InspectorControls,
				null,
				el( Panel, {
					className: props.attributes.className,
					allowNone: true,
					onChange: ( className, colour ) => {
						const attrs = { className };
						// A surface only works at full width; without one the width is left alone.
						if ( colour && props.attributes.align !== 'full' ) {
							attrs.align = 'full';
						}
						props.setAttributes( attrs );
					},
				} )
			)
		);
	}, 'withRmdSurfacePanel' );

	wp.hooks.addFilter( 'editor.BlockEdit', 'rmd-content-blocks/surface-panel', withSurfacePanel );

	// Inserter shortcuts: a group that already comes as a surface.
	wp.blocks.registerBlockVariation(
		'core/group',
		( surfaces.variations || [] ).map( ( v ) => ( {
			name: 'rmd-' + v.name,
			title: v.title,
			description: __( 'Section on a coloured surface across the full width.', 'rmd-content-blocks' ),
			icon: 'align-full-width',
			attributes: { align: 'full', className: v.class, layout: { type: 'constrained' } },
			scope: [ 'inserter' ],
			isActive: ( attrs ) => ( attrs.className || '' ).trim() === v.class,
		} ) )
	);
} )( window.wp, window.rmdContentBlocks );
