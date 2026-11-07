<?php
/**
 * "Recommendations" block: cards for someone else's offer -- picture, title,
 * short text and a button to the provider. Built for recommendations through
 * affiliate links, so the block carries the disclosure itself: every card is
 * marked, every link is rel="sponsored", and a notice sits under the cards.
 * An editor cannot forget it.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks\Render;

defined( 'ABSPATH' ) || exit;

final class Recommendations {

	/**
	 * Renders the block.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public static function render( array $attributes ): string {
		$items     = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : [];
		$affiliate = ! isset( $attributes['affiliate'] ) || (bool) $attributes['affiliate'];
		$notice    = isset( $attributes['notice'] ) ? trim( (string) $attributes['notice'] ) : '';
		$cards     = [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$title = isset( $item['title'] ) ? trim( (string) $item['title'] ) : '';
			$url   = isset( $item['url'] ) ? trim( (string) $item['url'] ) : '';

			// Without title and target it is a freshly added entry in the editor.
			if ( '' === $title || '' === $url ) {
				continue;
			}

			$label = isset( $item['buttonLabel'] ) ? trim( (string) $item['buttonLabel'] ) : '';

			$cards[] = [
				'title'     => $title,
				'text'      => isset( $item['text'] ) ? trim( (string) $item['text'] ) : '',
				'url'       => $url,
				'label'     => '' === $label ? __( 'Learn more', 'rmd-content-blocks' ) : $label,
				'image_id'  => isset( $item['imageId'] ) ? (int) $item['imageId'] : 0,
				'highlight' => ! empty( $item['highlight'] ),
			];
		}

		if ( ! $cards ) {
			return '';
		}

		$rel  = $affiliate ? 'sponsored noopener' : 'noopener';
		$html = '<div ' . get_block_wrapper_attributes( [ 'class' => 'rmd-recommendations' ] ) . '><div class="rmd-recommendations__grid">';

		foreach ( $cards as $card ) {
			// alt stays empty: the title next to it already names the card.
			$image   = $card['image_id'] ? wp_get_attachment_image( $card['image_id'], 'large', false, [ 'alt' => '' ] ) : '';
			$classes = 'rmd-recommendations__item';
			if ( $card['highlight'] ) {
				$classes .= ' rmd-recommendations__item--highlight';
			}
			if ( ! $image ) {
				$classes .= ' rmd-recommendations__item--textonly';
			}

			$html .= '<article class="' . esc_attr( $classes ) . '">';
			if ( $image ) {
				$html .= '<div class="rmd-recommendations__media rmd-fzp-frame">' . $image . '</div>';
			}
			$html .= '<div class="rmd-recommendations__body">';
			$html .= '<h3 class="rmd-recommendations__title">' . esc_html( $card['title'] ) . '</h3>';
			if ( '' !== $card['text'] ) {
				$html .= '<p class="rmd-recommendations__text">' . esc_html( $card['text'] ) . '</p>';
			}
			$html .= '<div class="rmd-recommendations__action">';
			$html .= sprintf(
				'<a class="wp-block-button__link wp-element-button" href="%1$s" target="_blank" rel="%2$s">%3$s</a>',
				esc_url( $card['url'] ),
				esc_attr( $rel ),
				esc_html( $card['label'] )
			);
			if ( $affiliate ) {
				$html .= '<span class="rmd-recommendations__badge">' . esc_html__( 'Advertisement · affiliate link*', 'rmd-content-blocks' ) . '</span>';
			}
			$html .= '</div></div></article>';
		}

		$html .= '</div>';

		if ( $affiliate ) {
			$html .= '<p class="rmd-recommendations__notice">' . esc_html( '' !== $notice ? $notice : self::default_notice() ) . '</p>';
		}

		return $html . '</div>';
	}

	/**
	 * Disclosure under the cards when the block has no own text.
	 */
	public static function default_notice(): string {
		return (string) apply_filters(
			'rmd_cb_recommendations_notice',
			__( '* Links marked as affiliate links are commission links. If you buy through such a link, we receive a small commission. The price stays the same for you.', 'rmd-content-blocks' )
		);
	}
}
