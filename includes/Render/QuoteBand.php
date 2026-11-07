<?php
/**
 * "Quote band" block: one sentence in large, light type on a coloured band
 * across the full width -- a claim, a quote, a pause between long sections.
 * The band takes a surface of the theme (see Surfaces), or its plain style.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks\Render;

use RMD\ContentBlocks\Surfaces;

defined( 'ABSPATH' ) || exit;

final class QuoteBand {

	/**
	 * Renders the block.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public static function render( array $attributes ): string {
		$text = trim( (string) ( $attributes['text'] ?? '' ) );
		if ( '' === $text ) {
			return '';
		}

		$author  = trim( (string) ( $attributes['author'] ?? '' ) );
		$classes = 'rmd-quote';

		// The className attribute carries the surface; get_block_wrapper_attributes() adds it again,
		// so only the theme default (if the block has no surface of its own) is added here.
		$own = Surfaces::classes( (string) ( $attributes['className'] ?? '' ) );
		if ( ! Surfaces::has_surface( $own ) ) {
			$default  = Surfaces::quote_classes( '' );
			$classes .= '' !== $default ? ' ' . $default : ' rmd-quote--plain';
		}

		$allowed = [
			'br'     => [],
			'em'     => [],
			'strong' => [],
		];

		return '<div ' . get_block_wrapper_attributes( [ 'class' => $classes ] ) . '><div class="rmd-quote__inner">'
			. '<p class="rmd-quote__text">' . wp_kses( $text, $allowed ) . '</p>'
			. ( '' !== $author ? '<p class="rmd-quote__author">' . wp_kses( $author, $allowed ) . '</p>' : '' )
			. '</div></div>';
	}
}
