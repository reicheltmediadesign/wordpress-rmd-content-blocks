/**
 * Moves the picture of every "Parallax image" block through its overhang while
 * the block crosses the viewport -- slower than the page, so it seems to lie
 * further back. Off with prefers-reduced-motion. Not loaded when the theme
 * declares add_theme_support( 'rmd-content-blocks-parallax' ).
 */
( function () {
	'use strict';

	if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}

	function init() {
		const blocks = Array.from( document.querySelectorAll( '.rmd-parallax' ) );
		if ( ! blocks.length || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		const visible = new Set();
		let ticking = false;

		function update() {
			ticking = false;
			const viewport = window.innerHeight;

			visible.forEach( function ( block ) {
				const image = block.querySelector( '.rmd-parallax__img' );
				if ( ! image ) {
					return;
				}

				const rect = block.getBoundingClientRect();
				const speed = parseFloat( block.dataset.parallaxSpeed ) || 1;
				// 0 when the top enters at the bottom of the viewport, 1 when the bottom leaves at the top.
				const progress = Math.min( 1, Math.max( 0, ( viewport - rect.top ) / ( viewport + rect.height ) ) );
				// The picture is 30 % taller than the frame: it may travel 15 % either way.
				const shift = ( progress - 0.5 ) * 2 * rect.height * 0.15 * speed;

				image.style.transform = 'translate3d(0, ' + shift.toFixed( 1 ) + 'px, 0)';
			} );
		}

		function request() {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		}

		const observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						visible.add( entry.target );
					} else {
						visible.delete( entry.target );
					}
				} );
				request();
			},
			{ rootMargin: '10% 0px' }
		);

		blocks.forEach( function ( block ) {
			observer.observe( block );
		} );

		window.addEventListener( 'scroll', request, { passive: true } );
		window.addEventListener( 'resize', request );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
