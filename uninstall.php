<?php
/**
 * Removes the plugin's options on every site. Content keeps its blocks; without
 * the plugin they show as unsupported blocks in the editor and render nothing.
 *
 * @package RMD\ContentBlocks
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes the options of the current site.
 */
function rmd_cb_uninstall_site(): void {
	foreach ( [ 'rmd_cb_version', 'external_updates-rmd-content-blocks' ] as $rmd_cb_option ) {
		delete_option( $rmd_cb_option );
	}
	delete_transient( 'rmd_cb_legacy_count' );
}

if ( is_multisite() ) {
	$rmd_cb_site_ids = get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	);
	foreach ( $rmd_cb_site_ids as $rmd_cb_site_id ) {
		switch_to_blog( (int) $rmd_cb_site_id );
		rmd_cb_uninstall_site();
		restore_current_blog();
	}
} else {
	rmd_cb_uninstall_site();
}
