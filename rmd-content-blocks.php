<?php
/**
 * Plugin Name:       RMD Content Blocks
 * Plugin URI:        https://github.com/reicheltmediadesign/wordpress-rmd-content-blocks
 * Description:       Content blocks for the block editor: page links, recommendations with affiliate disclosure, parallax image and quote band, plus a surface panel for groups. Themes adapt the look through CSS custom properties.
 * Version:           0.1.0
 * Requires at least: 6.8
 * Requires PHP:      8.1
 * Author:            Philipp Reichelt, reichelt media.design
 * Author URI:        https://reicheltmedia.design
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rmd-content-blocks
 * Domain Path:       /languages
 * Update URI:        https://github.com/reicheltmediadesign/wordpress-rmd-content-blocks
 *
 * @package RMD\ContentBlocks
 */

// This file must stay parseable by PHP 7.x so the version notice below can be shown instead of a fatal error.

defined( 'ABSPATH' ) || exit;

define( 'RMD_CB_VERSION', '0.1.0' );
define( 'RMD_CB_FILE', __FILE__ );
define( 'RMD_CB_DIR', plugin_dir_path( __FILE__ ) );
define( 'RMD_CB_URL', plugin_dir_url( __FILE__ ) );
define( 'RMD_CB_BASENAME', plugin_basename( __FILE__ ) );

if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html(
				sprintf(
					/* translators: 1: required PHP version, 2: current PHP version */
					__( 'RMD Content Blocks requires PHP %1$s or newer. This site runs PHP %2$s, so the plugin stays inactive.', 'rmd-content-blocks' ),
					'8.1',
					PHP_VERSION
				)
			);
			echo '</p></div>';
		}
	);
	return;
}

spl_autoload_register(
	function ( $class_name ) {
		$prefix = 'RMD\\ContentBlocks\\';
		if ( 0 !== strncmp( $class_name, $prefix, strlen( $prefix ) ) ) {
			return;
		}
		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = RMD_CB_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

if ( is_readable( RMD_CB_DIR . 'vendor/autoload.php' ) ) {
	require RMD_CB_DIR . 'vendor/autoload.php';
}

RMD\ContentBlocks\Plugin::boot();
