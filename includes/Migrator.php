<?php
/**
 * Moves content from legacy blocks (e.g. blocks a theme used to register) to
 * the blocks of this plugin. The theme or a site plugin tells which blocks to
 * replace with the filter "rmd_cb_migrations":
 *
 *     add_filter( 'rmd_cb_migrations', function ( $map ) {
 *         $map['mytheme/quote'] = [
 *             'name'       => 'rmd/quote-band',
 *             'attributes' => fn ( array $attrs ) => $attrs,
 *         ];
 *         return $map;
 *     } );
 *
 * Posts of every type and status (except revisions) and block widgets are
 * covered. Posts are saved with wp_update_post(), so a revision of the old
 * content remains.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks;

use RMD\ContentBlocks\Domain\BlockMigration;

defined( 'ABSPATH' ) || exit;

final class Migrator {

	public const COUNT_TRANSIENT = 'rmd_cb_legacy_count';

	/**
	 * The configured migration, or null when nothing is configured.
	 */
	public static function migration(): ?BlockMigration {
		$raw = apply_filters( 'rmd_cb_migrations', [] );
		$map = [];

		foreach ( is_array( $raw ) ? $raw : [] as $legacy => $target ) {
			if ( ! is_string( $legacy ) || ! is_array( $target ) || empty( $target['name'] ) ) {
				continue;
			}
			$name = (string) $target['name'];
			if ( ! in_array( substr( $name, 4 ), Blocks::NAMES, true ) || ! str_starts_with( $name, 'rmd/' ) ) {
				continue;
			}
			$map[ $legacy ] = [ 'name' => $name ];
			if ( isset( $target['attributes'] ) && is_callable( $target['attributes'] ) ) {
				$map[ $legacy ]['attributes'] = $target['attributes'];
			}
		}

		return $map ? new BlockMigration( $map ) : null;
	}

	/**
	 * IDs of posts that contain a legacy block.
	 *
	 * @return int[]
	 */
	public static function post_ids( BlockMigration $migration ): array {
		global $wpdb;

		$where = [];
		$args  = [];
		foreach ( $migration->legacy_names() as $name ) {
			$where[] = 'post_content LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( '<!-- wp:' . $name ) . '%';
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery -- the placeholders are built above; a one-off scan.
		$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type NOT IN ('revision') AND post_status <> 'auto-draft' AND (" . implode( ' OR ', $where ) . ') ORDER BY ID';
		$ids = $wpdb->get_col( $wpdb->prepare( $sql, $args ) );
		// phpcs:enable

		$out = [];
		foreach ( array_map( 'intval', (array) $ids ) as $id ) {
			// LIKE also matches longer names with the same prefix; check exactly.
			if ( $migration->mentions_legacy( (string) get_post_field( 'post_content', $id, 'raw' ) ) ) {
				$out[] = $id;
			}
		}
		return $out;
	}

	/**
	 * Block widgets that contain a legacy block.
	 *
	 * @return int[] Widget instance numbers.
	 */
	public static function widget_ids( BlockMigration $migration ): array {
		$out = [];
		foreach ( (array) get_option( 'widget_block', [] ) as $number => $instance ) {
			if ( is_array( $instance ) && isset( $instance['content'] ) && $migration->mentions_legacy( (string) $instance['content'] ) ) {
				$out[] = (int) $number;
			}
		}
		return $out;
	}

	/**
	 * Number of contents with legacy blocks; cached, cleared by run().
	 */
	public static function pending_count(): int {
		$migration = self::migration();
		if ( ! $migration ) {
			return 0;
		}

		$cached = get_transient( self::COUNT_TRANSIENT );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$count = count( self::post_ids( $migration ) ) + count( self::widget_ids( $migration ) );
		set_transient( self::COUNT_TRANSIENT, $count, 12 * HOUR_IN_SECONDS );
		return $count;
	}

	/**
	 * Migrates all contents.
	 *
	 * @return array{posts:int,widgets:int,blocks:int,errors:string[]}
	 */
	public static function run(): array {
		$result    = [
			'posts'   => 0,
			'widgets' => 0,
			'blocks'  => 0,
			'errors'  => [],
		];
		$migration = self::migration();
		if ( ! $migration ) {
			return $result;
		}

		// The content is the site's own: kses would otherwise strip markup for users without unfiltered_html.
		$kses = has_filter( 'content_save_pre', 'wp_filter_post_kses' );
		if ( $kses ) {
			kses_remove_filters();
		}

		foreach ( self::post_ids( $migration ) as $post_id ) {
			$migrated = $migration->migrate( parse_blocks( (string) get_post_field( 'post_content', $post_id, 'raw' ) ) );
			if ( ! $migrated['changed'] ) {
				continue;
			}

			$saved = wp_update_post(
				[
					'ID'           => $post_id,
					'post_content' => wp_slash( serialize_blocks( $migrated['blocks'] ) ),
				],
				true
			);

			if ( is_wp_error( $saved ) ) {
				/* translators: 1: post ID, 2: error message */
				$result['errors'][] = sprintf( __( 'Post %1$d: %2$s', 'rmd-content-blocks' ), $post_id, $saved->get_error_message() );
				continue;
			}

			++$result['posts'];
			$result['blocks'] += $migrated['changed'];
		}

		if ( $kses ) {
			kses_init_filters();
		}

		$widgets = (array) get_option( 'widget_block', [] );
		$changed = false;
		foreach ( self::widget_ids( $migration ) as $number ) {
			$migrated = $migration->migrate( parse_blocks( (string) $widgets[ $number ]['content'] ) );
			if ( $migrated['changed'] ) {
				$widgets[ $number ]['content'] = serialize_blocks( $migrated['blocks'] );
				$result['blocks']             += $migrated['changed'];
				++$result['widgets'];
				$changed = true;
			}
		}
		if ( $changed ) {
			update_option( 'widget_block', $widgets );
		}

		delete_transient( self::COUNT_TRANSIENT );
		return $result;
	}
}
