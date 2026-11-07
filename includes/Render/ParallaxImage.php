<?php
/**
 * "Parallax image" block: a picture between two sections that drifts slower
 * than the page while it scrolls past, with an optional sentence on top.
 *
 * The picture is taller than its frame; assets/js/parallax.js moves it through
 * that extra height. A theme that drives scrolling itself declares
 * add_theme_support( 'rmd-content-blocks-parallax' ) and moves
 * .rmd-parallax__img on its own.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks\Render;

use RMD\ContentBlocks\Blocks;

defined( 'ABSPATH' ) || exit;

final class ParallaxImage {

	public const HEIGHTS = [ 'small', 'medium', 'large' ];

	/** Share of the maximum drift: 1 uses the full overhang of the picture, more would show its edges. */
	public const SPEEDS = [
		'soft'   => '0.5',
		'strong' => '1',
	];

	/**
	 * Renders the block.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public static function render( array $attributes ): string {
		$image_id = (int) ( $attributes['imageId'] ?? 0 );
		$url      = $image_id ? (string) wp_get_attachment_image_url( $image_id, 'full' ) : '';
		if ( '' === $url ) {
			$url = (string) ( $attributes['imageUrl'] ?? '' );
		}
		if ( '' === $url ) {
			return '';
		}

		$height = in_array( $attributes['height'] ?? '', self::HEIGHTS, true ) ? (string) $attributes['height'] : 'medium';
		$shape  = self::shape( (string) ( $attributes['shape'] ?? '' ) );
		$motion = isset( self::SPEEDS[ $attributes['motion'] ?? '' ] ) ? (string) $attributes['motion'] : 'strong';
		$text   = trim( (string) ( $attributes['text'] ?? '' ) );
		$alt    = (string) ( $attributes['alt'] ?? '' );

		$classes = [ 'rmd-parallax', 'rmd-parallax--' . $height, 'rmd-parallax--' . $shape, 'rmd-fzp-frame' ];
		if ( '' !== $text ) {
			$classes[] = 'rmd-parallax--has-text';
		}

		$img_attributes = [
			'class'    => 'rmd-parallax__img',
			'alt'      => $alt,
			'loading'  => 'lazy',
			'decoding' => 'async',
		];
		$image          = $image_id
			? wp_get_attachment_image( $image_id, 'full', false, $img_attributes )
			: sprintf( '<img class="rmd-parallax__img" src="%1$s" alt="%2$s" loading="lazy" decoding="async">', esc_url( $url ), esc_attr( $alt ) );

		Blocks::enqueue_parallax();

		$wrapper = get_block_wrapper_attributes(
			[
				'class'               => implode( ' ', $classes ),
				'data-parallax-speed' => self::SPEEDS[ $motion ],
			]
		);

		return '<figure ' . $wrapper . '>' . $image
			. ( '' !== $text ? '<p class="rmd-parallax__text">' . wp_kses( $text, [ 'br' => [] ] ) . '</p>' : '' )
			. '</figure>';
	}

	/**
	 * Shapes offered in the editor. Themes add their own with "rmd_cb_parallax_shapes"
	 * (e.g. a wave edge they draw themselves on .rmd-parallax--<value>).
	 *
	 * @return array<int,array{value:string,label:string}>
	 */
	public static function shapes(): array {
		$shapes = apply_filters(
			'rmd_cb_parallax_shapes',
			[
				[
					'value' => 'straight',
					'label' => __( 'Straight (full width)', 'rmd-content-blocks' ),
				],
				[
					'value' => 'arch',
					'label' => __( 'Arch (curved edges, full width)', 'rmd-content-blocks' ),
				],
				[
					'value' => 'round',
					'label' => __( 'Round (inset, rounded corners)', 'rmd-content-blocks' ),
				],
			]
		);

		$out = [];
		foreach ( is_array( $shapes ) ? $shapes : [] as $shape ) {
			if ( is_array( $shape ) && ! empty( $shape['value'] ) && ! empty( $shape['label'] ) ) {
				$out[] = [
					'value' => sanitize_html_class( (string) $shape['value'] ),
					'label' => (string) $shape['label'],
				];
			}
		}
		return $out;
	}

	/**
	 * Shape for new blocks (filter "rmd_cb_parallax_default_shape").
	 */
	public static function default_shape(): string {
		return self::shape( (string) apply_filters( 'rmd_cb_parallax_default_shape', 'straight' ), 'straight' );
	}

	/**
	 * A known shape, else the default.
	 *
	 * @param string $shape    Requested shape.
	 * @param string $fallback Used when the shape is unknown ('' = default shape).
	 */
	private static function shape( string $shape, string $fallback = '' ): string {
		$values = array_column( self::shapes(), 'value' );
		if ( in_array( $shape, $values, true ) ) {
			return $shape;
		}
		return '' !== $fallback ? $fallback : self::default_shape();
	}
}
