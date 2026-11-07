/**
 * Editor for "Parallax image". The block is dynamic (render.php); the editor
 * shows the still picture with the same classes as the site. Shapes come from
 * PHP (filter "rmd_cb_parallax_shapes"), so a theme can add its own.
 */
( function ( wp, config ) {
	'use strict';

	const el = wp.element.createElement;
	const { Fragment } = wp.element;
	const { InspectorControls, MediaPlaceholder, MediaUpload, MediaUploadCheck, RichText, useBlockProps } = wp.blockEditor;
	const { PanelBody, Button, SelectControl, TextareaControl } = wp.components;
	const { __ } = wp.i18n;

	const parallax = ( config && config.parallax ) || {};
	const SHAPES = parallax.shapes || [];
	const DEFAULT_SHAPE = parallax.defaultShape || 'straight';

	const HEIGHTS = [
		{ label: __( 'Small', 'rmd-content-blocks' ), value: 'small' },
		{ label: __( 'Medium', 'rmd-content-blocks' ), value: 'medium' },
		{ label: __( 'Large', 'rmd-content-blocks' ), value: 'large' },
	];

	const MOTIONS = [
		{ label: __( 'Soft', 'rmd-content-blocks' ), value: 'soft' },
		{ label: __( 'Strong', 'rmd-content-blocks' ), value: 'strong' },
	];

	const shapeOf = ( value ) => ( SHAPES.some( ( s ) => s.value === value ) ? value : DEFAULT_SHAPE );

	wp.blocks.registerBlockType( 'rmd/parallax-image', {
		edit( props ) {
			const { attributes, setAttributes, isSelected } = props;
			const { imageId, imageUrl, alt, height, shape, motion, text } = attributes;
			const currentShape = shapeOf( shape );

			const blockProps = useBlockProps( {
				className: [
					'rmd-parallax',
					'rmd-parallax--' + ( height || 'medium' ),
					'rmd-parallax--' + currentShape,
					text ? 'rmd-parallax--has-text' : '',
				].filter( Boolean ).join( ' ' ),
			} );

			const onSelectImage = ( media ) => setAttributes( { imageId: media.id, imageUrl: media.url, alt: media.alt || '' } );

			const inspector = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Image', 'rmd-content-blocks' ) },
					el(
						MediaUploadCheck,
						{},
						el( MediaUpload, {
							onSelect: onSelectImage,
							allowedTypes: [ 'image' ],
							value: imageId,
							render: ( media ) =>
								el(
									Button,
									{ variant: 'secondary', onClick: media.open },
									imageUrl ? __( 'Replace image', 'rmd-content-blocks' ) : __( 'Select image', 'rmd-content-blocks' )
								),
						} )
					),
					imageUrl &&
						el( TextareaControl, {
							label: __( 'Alternative text', 'rmd-content-blocks' ),
							help: __( 'Describes the image for screen readers. Leave empty if it is purely decorative.', 'rmd-content-blocks' ),
							value: alt,
							onChange: ( value ) => setAttributes( { alt: value } ),
							__nextHasNoMarginBottom: true,
						} )
				),
				el(
					PanelBody,
					{ title: __( 'Appearance', 'rmd-content-blocks' ) },
					el( SelectControl, {
						label: __( 'Shape', 'rmd-content-blocks' ),
						value: currentShape,
						options: SHAPES,
						onChange: ( value ) => setAttributes( { shape: value } ),
						__nextHasNoMarginBottom: true,
					} ),
					el( SelectControl, {
						label: __( 'Height', 'rmd-content-blocks' ),
						value: height,
						options: HEIGHTS,
						onChange: ( value ) => setAttributes( { height: value } ),
						__nextHasNoMarginBottom: true,
					} ),
					el( SelectControl, {
						label: __( 'Motion', 'rmd-content-blocks' ),
						value: motion,
						options: MOTIONS,
						onChange: ( value ) => setAttributes( { motion: value } ),
						__nextHasNoMarginBottom: true,
					} )
				)
			);

			if ( ! imageUrl ) {
				return el(
					Fragment,
					{},
					inspector,
					el(
						'div',
						useBlockProps(),
						el( MediaPlaceholder, {
							icon: 'cover-image',
							labels: {
								title: __( 'Parallax image', 'rmd-content-blocks' ),
								instructions: __( 'A landscape picture with calm edges works best.', 'rmd-content-blocks' ),
							},
							onSelect: onSelectImage,
							accept: 'image/*',
							allowedTypes: [ 'image' ],
						} )
					)
				);
			}

			return el(
				Fragment,
				{},
				inspector,
				el(
					'figure',
					blockProps,
					el( 'img', { className: 'rmd-parallax__img', src: imageUrl, alt } ),
					( text || isSelected ) &&
						el( RichText, {
							tagName: 'p',
							className: 'rmd-parallax__text',
							value: text,
							onChange: ( value ) => setAttributes( { text: value } ),
							placeholder: __( 'Sentence on the image (optional) …', 'rmd-content-blocks' ),
							allowedFormats: [],
						} )
				)
			);
		},

		save() {
			return null;
		},
	} );
} )( window.wp, window.rmdContentBlocks );
