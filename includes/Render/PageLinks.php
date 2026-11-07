<?php
/**
 * "Page links" block: up to three cards that look like a small gallery but
 * link onward. Each card takes the featured image and the menu label of the
 * target page, read on every request, so renaming a menu entry or swapping a
 * featured image carries through to blocks placed long before.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks\Render;

defined( 'ABSPATH' ) || exit;

final class PageLinks {

	/** Three cards fill the content width; a fourth would start a gallery. */
	public const MAX_ITEMS = 3;

	/**
	 * Renders the block.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public static function render( array $attributes ): string {
		$items = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : [];
		$cards = [];

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$card = self::resolve( $item );

			// Entries without a target are skipped: a freshly added entry in the editor is exactly that.
			if ( $card ) {
				// Position in the list, so the editor preview can mark the right entry even when an empty one was skipped.
				$card['entry'] = (int) $index + 1;
				$cards[]       = $card;
			}

			if ( self::MAX_ITEMS === count( $cards ) ) {
				break;
			}
		}

		if ( ! $cards ) {
			return '';
		}

		// The entry number is only needed by the editor preview (ServerSideRender runs over REST).
		$is_preview = defined( 'REST_REQUEST' ) && REST_REQUEST;
		$wrapper    = get_block_wrapper_attributes( [ 'class' => 'rmd-page-links-box' ] );

		$html  = '<div ' . $wrapper . '>';
		$html .= sprintf( '<div class="rmd-page-links" style="%s">', esc_attr( sprintf( '--rmd-page-links-columns: %d;', count( $cards ) ) ) );

		foreach ( $cards as $card ) {
			// alt stays empty: the label in the same link already names the target.
			$image = $card['image_id'] ? wp_get_attachment_image( $card['image_id'], 'large', false, [ 'alt' => '' ] ) : '';

			// An external target here is a partner, its image a logo: shown whole instead of cropped.
			$modifier = '';
			if ( ! $image ) {
				$modifier = ' rmd-page-links__item--textonly';
			} elseif ( $card['external'] ) {
				$modifier = ' rmd-page-links__item--logo';
			}

			$html .= sprintf(
				'<a class="rmd-page-links__item%1$s" href="%2$s"%3$s%4$s>',
				esc_attr( $modifier ),
				esc_url( $card['url'] ),
				$card['external'] ? ' target="_blank" rel="noopener"' : '',
				$is_preview ? ' data-entry="' . esc_attr( (string) $card['entry'] ) . '"' : ''
			);
			if ( $image ) {
				$html .= '<div class="rmd-page-links__media rmd-fzp-frame">' . $image . '</div>';
			}
			$html .= '<span class="rmd-page-links__label">' . esc_html( $card['label'] ) . '</span>';
			$html .= '</a>';
		}

		return $html . '</div></div>';
	}

	/**
	 * Resolves one entry into URL, label and image.
	 *
	 * @param array<string,mixed> $item Entry as stored in the block attributes.
	 * @return array{url:string,label:string,image_id:int,external:bool}|null Null when the entry has no target yet.
	 */
	public static function resolve( array $item ): ?array {
		$source   = isset( $item['source'] ) ? (string) $item['source'] : 'page';
		$label    = isset( $item['label'] ) ? trim( (string) $item['label'] ) : '';
		$image_id = isset( $item['imageId'] ) ? (int) $item['imageId'] : 0;

		if ( 'external' === $source ) {
			$url = isset( $item['url'] ) ? trim( (string) $item['url'] ) : '';
			if ( '' === $url ) {
				return null;
			}

			if ( '' === $label ) {
				// An external target has no menu entry; the host name is short and recognisable.
				$host  = wp_parse_url( $url, PHP_URL_HOST );
				$label = $host ? (string) preg_replace( '/^www\./', '', $host ) : $url;
			}

			return [
				'url'      => $url,
				'label'    => $label,
				'image_id' => $image_id,
				'external' => true,
			];
		}

		$page_id = isset( $item['pageId'] ) ? (int) $item['pageId'] : 0;

		// A page moved to the trash or back to draft drops its card instead of pointing nowhere.
		if ( ! $page_id || 'publish' !== get_post_status( $page_id ) ) {
			return null;
		}

		if ( '' === $label ) {
			$label = self::menu_label( $page_id );
		}

		if ( ! $image_id ) {
			$image_id = (int) get_post_thumbnail_id( $page_id );
		}

		// Limited to the characters of an HTML id; a typed "#" or spaces are dropped.
		$anchor = isset( $item['anchor'] ) ? (string) preg_replace( '/[^A-Za-z0-9_\-:.]/', '', ltrim( trim( (string) $item['anchor'] ), '#' ) ) : '';

		return [
			'url'      => (string) get_permalink( $page_id ) . ( '' !== $anchor ? '#' . $anchor : '' ),
			'label'    => $label,
			'image_id' => $image_id,
			'external' => false,
		];
	}

	/**
	 * Label a page carries in the main menu, else its title. The menu label is
	 * the wording visitors know a page by, and usually the shorter one.
	 *
	 * @param int $page_id Page ID.
	 */
	public static function menu_label( int $page_id ): string {
		static $labels = null;

		if ( null === $labels ) {
			$labels   = [];
			$location = self::menu_location();
			$menus    = get_nav_menu_locations();
			$items    = '' !== $location && ! empty( $menus[ $location ] ) ? wp_get_nav_menu_items( $menus[ $location ] ) : [];

			foreach ( (array) $items as $menu_item ) {
				// The first match wins: when a page is in the menu twice, the topmost entry counts.
				if ( 'post_type' === $menu_item->type && ! isset( $labels[ (int) $menu_item->object_id ] ) ) {
					$labels[ (int) $menu_item->object_id ] = (string) $menu_item->title;
				}
			}
		}

		return $labels[ $page_id ] ?? (string) get_the_title( $page_id );
	}

	/**
	 * Menu location to read labels from: filter "rmd_cb_page_links_menu_location",
	 * else the first location that has a menu assigned.
	 */
	private static function menu_location(): string {
		$location = (string) apply_filters( 'rmd_cb_page_links_menu_location', '' );
		if ( '' !== $location ) {
			return $location;
		}

		foreach ( get_nav_menu_locations() as $name => $menu_id ) {
			if ( $menu_id ) {
				return (string) $name;
			}
		}

		return '';
	}
}
