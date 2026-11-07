<?php
/**
 * Tools → Content blocks: replaces legacy blocks with the blocks of this
 * plugin. Only present when a theme or plugin configured a migration
 * (filter "rmd_cb_migrations"); a notice points to it while contents with
 * legacy blocks remain.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks\Admin;

use RMD\ContentBlocks\Migrator;
use RMD\ContentBlocks\Plugin;

defined( 'ABSPATH' ) || exit;

final class MigrationPage {

	public const SLUG   = 'rmd-content-blocks';
	public const ACTION = 'rmd_cb_migrate';

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ] );
		add_action( 'admin_notices', [ self::class, 'notice' ] );
		add_action( 'admin_post_' . self::ACTION, [ self::class, 'handle' ] );
	}

	public static function menu(): void {
		if ( ! Migrator::migration() ) {
			return;
		}

		add_management_page(
			__( 'Content blocks', 'rmd-content-blocks' ),
			__( 'Content blocks', 'rmd-content-blocks' ),
			Plugin::CAP,
			self::SLUG,
			[ self::class, 'render' ]
		);
	}

	public static function url( array $args = [] ): string {
		return add_query_arg( array_merge( [ 'page' => self::SLUG ], $args ), admin_url( 'tools.php' ) );
	}

	public static function notice(): void {
		if ( ! current_user_can( Plugin::CAP ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && 'tools_page_' . self::SLUG === $screen->id ) {
			return;
		}

		$count = Migrator::pending_count();
		if ( $count < 1 ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of posts and widgets */
					_n( '%d content still uses blocks that RMD Content Blocks replaces.', '%d contents still use blocks that RMD Content Blocks replaces.', $count, 'rmd-content-blocks' ),
					$count
				)
			),
			esc_url( self::url() ),
			esc_html__( 'Migrate now', 'rmd-content-blocks' )
		);
	}

	public static function render(): void {
		if ( ! current_user_can( Plugin::CAP ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'rmd-content-blocks' ) );
		}

		$migration = Migrator::migration();
		$posts     = $migration ? Migrator::post_ids( $migration ) : [];
		$widgets   = $migration ? Migrator::widget_ids( $migration ) : [];

		echo '<div class="wrap"><h1>' . esc_html__( 'Content blocks', 'rmd-content-blocks' ) . '</h1>';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display of the result only.
		$done = isset( $_GET['done'] ) ? get_transient( 'rmd_cb_migration_result_' . get_current_user_id() ) : false;
		if ( is_array( $done ) ) {
			delete_transient( 'rmd_cb_migration_result_' . get_current_user_id() );
			printf(
				'<div class="notice notice-%1$s"><p>%2$s</p>%3$s</div>',
				$done['errors'] ? 'warning' : 'success',
				esc_html(
					sprintf(
						/* translators: 1: number of blocks, 2: number of posts, 3: number of widgets */
						__( 'Migrated %1$d blocks in %2$d posts and %3$d widgets.', 'rmd-content-blocks' ),
						(int) $done['blocks'],
						(int) $done['posts'],
						(int) $done['widgets']
					)
				),
				$done['errors'] ? '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $done['errors'] ) ) . '</li></ul>' : ''
			);
		}

		echo '<p>' . esc_html__( 'Replaces blocks of the theme with the blocks of RMD Content Blocks. Settings and texts of the blocks are taken over; the look comes from the plugin and the theme afterwards.', 'rmd-content-blocks' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Replaced blocks:', 'rmd-content-blocks' ) . '</strong> <code>' . implode( '</code>, <code>', array_map( 'esc_html', $migration ? $migration->legacy_names() : [] ) ) . '</code></p>';

		if ( ! $posts && ! $widgets ) {
			echo '<p>' . esc_html__( 'No content uses these blocks any more. Nothing to do.', 'rmd-content-blocks' ) . '</p></div>';
			return;
		}

		echo '<p>' . esc_html__( 'Contents with these blocks:', 'rmd-content-blocks' ) . '</p><ul class="ul-disc">';
		foreach ( $posts as $post_id ) {
			printf(
				'<li><a href="%1$s">%2$s</a> <span class="description">(%3$s, %4$s)</span></li>',
				esc_url( (string) get_edit_post_link( $post_id ) ),
				esc_html( get_the_title( $post_id ) ?: '#' . $post_id ),
				esc_html( (string) get_post_type( $post_id ) ),
				esc_html( (string) get_post_status( $post_id ) )
			);
		}
		if ( $widgets ) {
			/* translators: %d: number of widgets */
			echo '<li>' . esc_html( sprintf( _n( '%d block widget', '%d block widgets', count( $widgets ), 'rmd-content-blocks' ), count( $widgets ) ) ) . '</li>';
		}
		echo '</ul>';

		echo '<p>' . esc_html__( 'Each post is saved once, so its previous version stays available as a revision.', 'rmd-content-blocks' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
		wp_nonce_field( self::ACTION );
		submit_button( __( 'Migrate blocks now', 'rmd-content-blocks' ), 'primary', 'submit', false );
		echo '</form></div>';
	}

	public static function handle(): void {
		check_admin_referer( self::ACTION );
		if ( ! current_user_can( Plugin::CAP ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'rmd-content-blocks' ), '', [ 'response' => 403 ] );
		}

		set_transient( 'rmd_cb_migration_result_' . get_current_user_id(), Migrator::run(), 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( self::url( [ 'done' => 1 ] ) );
		exit;
	}
}
