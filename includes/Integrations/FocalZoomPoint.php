<?php
/**
 * RMD Focal Zoom Point: the parallax image gets the "image section & zoom"
 * panel and its per-block override. Cards in page links and recommendations
 * are rendered with wp_get_attachment_image() inside an .rmd-fzp-frame, which
 * the plugin handles on its own. Harmless while that plugin is inactive.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks\Integrations;

defined( 'ABSPATH' ) || exit;

final class FocalZoomPoint {

	public static function init(): void {
		add_filter( 'rmd_fzp_blocks', [ self::class, 'blocks' ] );
	}

	/**
	 * @param mixed $blocks Block name => attribute holding the attachment ID.
	 * @return array<string,string>
	 */
	public static function blocks( $blocks ): array {
		$blocks                       = is_array( $blocks ) ? $blocks : [];
		$blocks['rmd/parallax-image'] = 'imageId';
		return $blocks;
	}
}
