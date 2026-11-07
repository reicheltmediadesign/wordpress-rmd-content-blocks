/**
 * Editor for "Recommendations": cards are edited in the sidebar, the canvas
 * shows the server rendering -- including the affiliate disclosure, which the
 * editor never types by hand.
 */
( function ( wp ) {
	'use strict';

	const el = wp.element.createElement;
	const { Fragment } = wp.element;
	const { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } = wp.blockEditor;
	const { PanelBody, Button, TextControl, TextareaControl, ToggleControl, Placeholder, Disabled } = wp.components;
	const { __, sprintf } = wp.i18n;
	const ServerSideRender = wp.serverSideRender;

	const EMPTY_ITEM = { imageId: 0, imageUrl: '', title: '', text: '', url: '', buttonLabel: '', highlight: false };

	wp.blocks.registerBlockType( 'rmd/recommendations', {
		edit( props ) {
			const { attributes, setAttributes } = props;
			const items = attributes.items || [];
			const blockProps = useBlockProps();

			// Lists are replaced, never mutated: the editor compares attributes by identity.
			const updateItem = ( index, changes ) =>
				setAttributes( { items: items.map( ( item, i ) => ( i === index ? Object.assign( {}, item, changes ) : item ) ) } );
			const removeItem = ( index ) => setAttributes( { items: items.filter( ( item, i ) => i !== index ) } );
			const moveItem = ( index, offset ) => {
				const target = index + offset;
				if ( target < 0 || target >= items.length ) {
					return;
				}
				const next = items.slice();
				next.splice( target, 0, next.splice( index, 1 )[ 0 ] );
				setAttributes( { items: next } );
			};
			const addItem = () => setAttributes( { items: items.concat( [ Object.assign( {}, EMPTY_ITEM ) ] ) } );

			const itemPanel = ( item, index ) =>
				el(
					PanelBody,
					{
						key: index,
						/* translators: 1: position of the card, 2: its title. */
						title: sprintf( __( 'Card %1$d: %2$s', 'rmd-content-blocks' ), index + 1, item.title || '…' ),
						initialOpen: false,
					},
					el( TextControl, {
						label: __( 'Title', 'rmd-content-blocks' ),
						value: item.title || '',
						onChange: ( title ) => updateItem( index, { title } ),
					} ),
					el( TextareaControl, {
						label: __( 'Text', 'rmd-content-blocks' ),
						value: item.text || '',
						onChange: ( text ) => updateItem( index, { text } ),
					} ),
					el( TextControl, {
						label: __( 'Link', 'rmd-content-blocks' ),
						value: item.url || '',
						placeholder: 'https://',
						onChange: ( url ) => updateItem( index, { url } ),
					} ),
					el( TextControl, {
						label: __( 'Button label', 'rmd-content-blocks' ),
						value: item.buttonLabel || '',
						placeholder: __( 'Learn more', 'rmd-content-blocks' ),
						help: __( 'Leave empty for the default.', 'rmd-content-blocks' ),
						onChange: ( buttonLabel ) => updateItem( index, { buttonLabel } ),
					} ),
					el( ToggleControl, {
						label: __( 'Highlight (whole row)', 'rmd-content-blocks' ),
						checked: !! item.highlight,
						onChange: ( highlight ) => updateItem( index, { highlight } ),
					} ),
					el(
						MediaUploadCheck,
						{},
						el( MediaUpload, {
							onSelect: ( media ) => updateItem( index, { imageId: media.id, imageUrl: media.url } ),
							allowedTypes: [ 'image' ],
							value: item.imageId,
							render: ( media ) =>
								el(
									Button,
									{ variant: 'secondary', onClick: media.open },
									item.imageId ? __( 'Replace image', 'rmd-content-blocks' ) : __( 'Select image', 'rmd-content-blocks' )
								),
						} )
					),
					item.imageId
						? el(
							Button,
							{ variant: 'link', onClick: () => updateItem( index, { imageId: 0, imageUrl: '' } ), style: { marginTop: '8px' } },
							__( 'Remove image', 'rmd-content-blocks' )
						)
						: null,
					el(
						'div',
						{ style: { display: 'flex', gap: '8px', marginTop: '16px' } },
						el( Button, { variant: 'secondary', disabled: 0 === index, onClick: () => moveItem( index, -1 ) }, __( 'Move up', 'rmd-content-blocks' ) ),
						el(
							Button,
							{ variant: 'secondary', disabled: index === items.length - 1, onClick: () => moveItem( index, 1 ) },
							__( 'Move down', 'rmd-content-blocks' )
						)
					),
					el(
						Button,
						{ variant: 'link', isDestructive: true, onClick: () => removeItem( index ), style: { display: 'block', marginTop: '16px' } },
						__( 'Remove card', 'rmd-content-blocks' )
					)
				);

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Settings', 'rmd-content-blocks' ) },
						el( ToggleControl, {
							label: __( 'Affiliate links', 'rmd-content-blocks' ),
							help: __( 'Marks every card as advertising and adds the notice below the cards. Only switch off if there is no commission.', 'rmd-content-blocks' ),
							checked: false !== attributes.affiliate,
							onChange: ( affiliate ) => setAttributes( { affiliate } ),
						} ),
						false !== attributes.affiliate &&
							el( TextareaControl, {
								label: __( 'Notice below the cards', 'rmd-content-blocks' ),
								help: __( 'Leave empty for the default text.', 'rmd-content-blocks' ),
								value: attributes.notice || '',
								onChange: ( notice ) => setAttributes( { notice } ),
							} ),
						el( Button, { variant: 'secondary', onClick: addItem }, __( 'Add card', 'rmd-content-blocks' ) )
					),
					items.map( itemPanel )
				),
				el(
					'div',
					blockProps,
					items.length
						? el( Disabled, null, el( ServerSideRender, { block: 'rmd/recommendations', attributes } ) )
						: el(
							Placeholder,
							{
								icon: 'products',
								label: __( 'Recommendations', 'rmd-content-blocks' ),
								instructions: __( 'Cards with image, title, text and a button to an external offer.', 'rmd-content-blocks' ),
							},
							el( Button, { variant: 'primary', onClick: addItem }, __( 'Add card', 'rmd-content-blocks' ) )
						)
				)
			);
		},

		save() {
			return null;
		},
	} );
} )( window.wp );
