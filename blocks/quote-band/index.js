/**
 * Editor for "Quote band": the sentence and its source are edited in place,
 * the surface in the sidebar. The block is dynamic (render.php); the editor
 * shows the same markup classes, so theme and plugin styles apply as on the site.
 */
( function ( wp ) {
	'use strict';

	const el = wp.element.createElement;
	const { Fragment } = wp.element;
	const { InspectorControls, RichText, useBlockProps } = wp.blockEditor;
	const { __ } = wp.i18n;
	const surfaces = window.rmdContentBlocksSurfaces;

	wp.blocks.registerBlockType( 'rmd/quote-band', {
		edit( props ) {
			const { attributes, setAttributes } = props;
			const own = attributes.className || '';
			const hasOwn = surfaces && surfaces.surfaceOf( own );
			// Without a surface of its own the band shows the theme default, as on the site.
			const shown = hasOwn || ! surfaces || ! surfaces.quoteDefault ? own : ( own + ' ' + surfaces.quoteDefault ).trim();
			const plain = ! surfaces || ! ( hasOwn || surfaces.quoteDefault );

			const blockProps = useBlockProps( {
				className: [ 'rmd-quote', plain ? 'rmd-quote--plain' : '', hasOwn ? '' : shown ].filter( Boolean ).join( ' ' ),
			} );

			return el(
				Fragment,
				{},
				surfaces && surfaces.hasSurfaces
					? el(
						InspectorControls,
						{},
						el( surfaces.Panel, {
							className: shown,
							allowNone: false,
							initialOpen: true,
							onChange: ( next ) => setAttributes( { className: next } ),
						} )
					)
					: null,
				el(
					'div',
					blockProps,
					el(
						'div',
						{ className: 'rmd-quote__inner' },
						el( RichText, {
							tagName: 'p',
							className: 'rmd-quote__text',
							value: attributes.text,
							onChange: ( text ) => setAttributes( { text } ),
							placeholder: __( 'Enter a sentence …', 'rmd-content-blocks' ),
							allowedFormats: [ 'core/bold', 'core/italic' ],
						} ),
						el( RichText, {
							tagName: 'p',
							className: 'rmd-quote__author',
							value: attributes.author,
							onChange: ( author ) => setAttributes( { author } ),
							placeholder: __( 'Source (optional)', 'rmd-content-blocks' ),
							allowedFormats: [],
						} )
					)
				)
			);
		},

		save() {
			return null;
		},
	} );
} )( window.wp );
