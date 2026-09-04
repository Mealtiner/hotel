/**
 * GARRY — Jídelníček: editor UI (nativní WordPress/Divi 5 blok, GRID-SUITE
 * atomizace §"Každý text/nadpis/tlačítko/obrázek jako samostatný Divi 5
 * widget — totéž pro menu"). Čistě standardní WP Block Editor API
 * (wp.blocks/wp.element/wp.components/wp.blockEditor/wp.serverSideRender),
 * žádný build krok, žádná závislost na interních Divi balíčcích — blok se
 * v Divi 5 inserteru objeví stejně jako vlastní Divi moduly, protože Divi 5
 * canvas JE WordPress Block Editor (viz <!-- wp:divi/... --> markup).
 */
( function ( wp ) {
	'use strict';
	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ServerSideRender = wp.serverSideRender;
	var __ = wp.i18n.__;

	var DATA = window.GarryDenniMenuBlockData || { venues: [], types: [] };

	registerBlockType( 'garry/denni-menu', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var venueOptions = [ { label: __( '— vyberte provoz —', 'garry-denni-menu' ), value: '' } ].concat( DATA.venues );

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Jídelníček — nastavení', 'garry-denni-menu' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Provoz', 'garry-denni-menu' ),
							value: attributes.provoz,
							options: venueOptions,
							onChange: function ( v ) { setAttributes( { provoz: v } ); },
						} ),
						el( SelectControl, {
							label: __( 'Co vypsat', 'garry-denni-menu' ),
							value: attributes.typ,
							options: DATA.types,
							onChange: function ( v ) { setAttributes( { typ: v } ); },
						} )
					)
				),
				! attributes.provoz
					? el( 'div', { style: { padding: '24px', textAlign: 'center', background: '#f0f0f1', border: '1px dashed #c3c4c7' } },
						el( 'span', { className: 'dashicons dashicons-food', style: { fontSize: '28px', width: '28px', height: '28px', display: 'block', margin: '0 auto 8px' } } ),
						el( 'p', {}, __( 'GARRY — Jídelníček: vyberte provoz v postranním panelu vpravo.', 'garry-denni-menu' ) )
					)
					: el( ServerSideRender, { block: 'garry/denni-menu', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
