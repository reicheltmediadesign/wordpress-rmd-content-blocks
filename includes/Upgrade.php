<?php
/**
 * One-time data changes after a plugin update. The installed version is kept
 * in an option; add steps here guarded by a version comparison.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks;

defined( 'ABSPATH' ) || exit;

final class Upgrade {

	public const OPTION = 'rmd_cb_version';

	public static function run(): void {
		$installed = (string) get_option( self::OPTION, '' );
		if ( '' !== $installed && version_compare( $installed, RMD_CB_VERSION, '>=' ) ) {
			return;
		}

		update_option( self::OPTION, RMD_CB_VERSION, true );
	}
}
