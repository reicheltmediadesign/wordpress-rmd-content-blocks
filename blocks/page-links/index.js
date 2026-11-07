/**
 * Editor for "Page links": up to three entries in the sidebar, the canvas
 * shows the server rendering with labels and images resolved exactly as a
 * visitor sees them. Each entry has a colour badge; the matching card in the
 * preview carries the same colour (assets/css/editor.css).
 */
( function ( wp ) {
	'use strict';

	const el = wp.element.createElement;
	const { Fragment, useState } = wp.element;
	const { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } = wp.blockEditor;
	const { PanelBody, Button, SelectControl, TextControl, Placeholder, Spinner, Disabled } = wp.components;
	const { useSelect } = wp.data;
	const { __, sprintf } = wp.i18n;
	const ServerSideRender = wp.serverSideRender;

	const MAX_ITEMS = 3;
	const ENTRY_COLORS = [ '#2f8a7a', '#94750f', '#8a4f7d' ];
	const EMPTY_ITEM = { source: 'page', pageId: 0, anchor: '', url: '', label: '', imageId: 0, imageUrl: '' };

	const decode = ( html ) => {
		const area = document.createElement( 'textarea' );
		area.innerHTML = html;
		return area.value;
	};

	wp.blocks.registerBlockType( 'rmd/page-links', {
		edit( props ) {
			const { attributes, setAttributes, isSelected } = props;
			const items = attributes.items || [];
			const [ activeIndex, setActiveIndex ] = useState( 0 );

			// data-active drives the highlight in the preview, only while the block is selected.
			const blockProps = useBlockProps( {
				className: 'rmd-page-links-editor',
				'data-active': isSelected && null !== activeIndex ? String( activeIndex + 1 ) : undefined,
			} );

			const pages = useSelect(
				( select ) => select( 'core' ).getEntityRecords( 'postType', 'page', { per_page: 100, orderby: 'title', order: 'asc', status: 'publish' } ),
				[]
			);

			const pageOptions = [ { label: __( '— Select page —', 'rmd-content-blocks' ), value: '0' } ].concat(
				( pages || [] ).map( ( page ) => ( {
					label: decode( page.title.rendered ) || __( '(no title)', 'rmd-content-blocks' ),
					value: String( page.id ),
				} ) )
			);

			// Lists are replaced, never mutated: the editor compares attributes by identity.
			const updateItem = ( index, changes ) =>
				setAttributes( { items: items.map( ( item, i ) => ( i === index ? Object.assign( {}, item, changes ) : item ) ) } );
			const removeItem = ( index ) => {
				setAttributes( { items: items.filter( ( item, i ) => i !== index ) } );
				setActiveIndex( null );
			};
			const addItem = () => {
				setAttributes( { items: items.concat( [ Object.assign( {}, EMPTY_ITEM ) ] ) } );
				setActiveIndex( items.length );
			};

			const entryName = ( item ) => {
				if ( item.label ) {
					return item.label;
				}
				if ( 'external' === item.source ) {
					return item.url || '';
				}
				const page = ( pages || [] ).find( ( p ) => p.id === item.pageId );
				return page ? decode( page.title.rendered ) : '';
			};

			const entryBadge = ( index ) =>
				el(
					'span',
					{
						style: {
							display: 'inline-block',
							marginRight: '8px',
							padding: '2px 8px',
							borderRadius: '10px',
							background: ENTRY_COLORS[ index % ENTRY_COLORS.length ],
							color: '#fff',
							fontSize: '11px',
							fontWeight: 600,
							lineHeight: '16px',
						},
					},
					/* translators: %d: position of the entry, starting at 1. */
					sprintf( __( 'Entry %d', 'rmd-content-blocks' ), index + 1 )
				);

			const addButton = ( variant ) =>
				items.length < MAX_ITEMS && el( Button, { variant, onClick: addItem }, __( 'Add entry', 'rmd-content-blocks' ) );

			const itemPanel = ( item, index ) => {
				const isExternal = 'external' === item.source;

				return el(
					PanelBody,
					{
						key: index,
						title: el( Fragment, {}, entryBadge( index ), entryName( item ) ),
						opened: activeIndex === index,
						onToggle: () => setActiveIndex( activeIndex === index ? null : index ),
					},
					el( SelectControl, {
						label: __( 'Target', 'rmd-content-blocks' ),
						value: item.source || 'page',
						options: [
							{ label: __( 'Page', 'rmd-content-blocks' ), value: 'page' },
							{ label: __( 'External link', 'rmd-content-blocks' ), value: 'external' },
						],
						onChange: ( source ) => updateItem( index, { source } ),
					} ),
					isExternal
						? el( TextControl, {
							label: __( 'Address', 'rmd-content-blocks' ),
							value: item.url || '',
							placeholder: 'https://',
							onChange: ( url ) => updateItem( index, { url } ),
						} )
						: el( SelectControl, {
							label: __( 'Page', 'rmd-content-blocks' ),
							value: String( item.pageId || 0 ),
							options: pageOptions,
							onChange: ( pageId ) => updateItem( index, { pageId: parseInt( pageId, 10 ) } ),
						} ),
					// Only for pages: an external link carries its anchor in the address.
					isExternal
						? null
						: el( TextControl, {
							label: __( 'Anchor', 'rmd-content-blocks' ),
							value: item.anchor || '',
							placeholder: __( 'e.g. dates', 'rmd-content-blocks' ),
							help: __( 'Jumps to this place on the target page (HTML anchor of a block, without #). Leave empty for the top of the page.', 'rmd-content-blocks' ),
							onChange: ( anchor ) => updateItem( index, { anchor: anchor.replace( /^#+/, '' ).trim() } ),
						} ),
					el( TextControl, {
						label: __( 'Label', 'rmd-content-blocks' ),
						value: item.label || '',
						placeholder: isExternal ? __( 'Address of the website', 'rmd-content-blocks' ) : __( 'Label from the menu', 'rmd-content-blocks' ),
						help: __( 'Leave empty for the default.', 'rmd-content-blocks' ),
						onChange: ( label ) => updateItem( index, { label } ),
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
									item.imageId
										? __( 'Replace image', 'rmd-content-blocks' )
										: isExternal
											? __( 'Select image', 'rmd-content-blocks' )
											: __( 'Override image', 'rmd-content-blocks' )
								),
						} )
					),
					item.imageId
						? el(
							Button,
							{ variant: 'link', onClick: () => updateItem( index, { imageId: 0, imageUrl: '' } ), style: { marginTop: '8px' } },
							// For pages "remove" is not the end of the image but the return to the featured image.
							isExternal ? __( 'Remove image', 'rmd-content-blocks' ) : __( 'Use featured image', 'rmd-content-blocks' )
						)
						: null,
					el(
						Button,
						{ variant: 'link', isDestructive: true, onClick: () => removeItem( index ), style: { display: 'block', marginTop: '16px' } },
						__( 'Remove entry', 'rmd-content-blocks' )
					)
				);
			};

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					items.map( itemPanel ),
					el(
						PanelBody,
						{ title: __( 'Entries', 'rmd-content-blocks' ) },
						addButton( 'secondary' ),
						items.length >= MAX_ITEMS
							? el( 'p', { style: { margin: '8px 0 0', fontStyle: 'italic' } }, __( 'More than three cards do not fit in one row.', 'rmd-content-blocks' ) )
							: null
					)
				),
				el(
					'div',
					blockProps,
					items.length
						? el( Disabled, null, el( ServerSideRender, { block: 'rmd/page-links', attributes } ) )
						: el(
							Placeholder,
							{
								icon: 'admin-links',
								label: __( 'Page links', 'rmd-content-blocks' ),
								instructions: __( 'Up to three cards linking to other pages.', 'rmd-content-blocks' ),
							},
							null === pages ? el( Spinner ) : addButton( 'primary' )
						)
				)
			);
		},

		save() {
			return null;
		},
	} );
} )( window.wp );
