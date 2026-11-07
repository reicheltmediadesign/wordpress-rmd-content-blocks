<?php
/**
 * Plugin bootstrap: wires all parts to WordPress hooks.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** Running the migration of legacy blocks rewrites content: administrators only. */
	public const CAP = 'manage_options';

	private static bool $booted = false;

	public static function boot(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		add_action( 'init', [ self::class, 'load_textdomain' ], 1 );
		add_action( 'init', [ Upgrade::class, 'run' ], 5 );

		Updater::init();
		Blocks::init();
		Integrations\FocalZoomPoint::init();

		if ( is_admin() ) {
			Admin\MigrationPage::init();
		}
	}

	public static function load_textdomain(): void {
		load_plugin_textdomain( 'rmd-content-blocks', false, dirname( RMD_CB_BASENAME ) . '/languages' );
	}
}
