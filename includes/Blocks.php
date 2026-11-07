<?php
/**
 * Registers the blocks, the shared editor script with its configuration, the
 * stylesheets and the frontend parallax script.
 *
 * All blocks are dynamic: the editor saves only the block comment with its
 * attributes, the markup is rendered on every request (blocks/<name>/render.php).
 * That keeps saved content valid whatever the markup becomes in later versions.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks;

defined( 'ABSPATH' ) || exit;

final class Blocks {

	public const NAMES = [ 'page-links', 'recommendations', 'parallax-image', 'quote-band' ];

	public const EDITOR_HANDLE   = 'rmd-cb-editor';
	public const STYLE_HANDLE    = 'rmd-content-blocks';
	public const EDITOR_STYLE    = 'rmd-content-blocks-editor';
	public const PARALLAX_HANDLE = 'rmd-cb-parallax';

	/** Theme feature: the theme moves .rmd-parallax pictures itself, the plugin script stays off. */
	public const THEME_SUPPORT_PARALLAX = 'rmd-content-blocks-parallax';

	public static function init(): void {
		add_action( 'init', [ self::class, 'register' ] );
		add_action( 'enqueue_block_editor_assets', [ self::class, 'enqueue_editor' ] );
	}

	public static function register(): void {
		wp_register_script(
			self::EDITOR_HANDLE,
			RMD_CB_URL . 'assets/js/editor-shared.js',
			[ 'wp-blocks', 'wp-components', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-hooks', 'wp-i18n' ],
			self::version( 'assets/js/editor-shared.js' ),
			true
		);
		wp_set_script_translations( self::EDITOR_HANDLE, 'rmd-content-blocks', RMD_CB_DIR . 'languages' );
		wp_add_inline_script(
			self::EDITOR_HANDLE,
			'window.rmdContentBlocks = ' . wp_json_encode( self::editor_config() ) . ';',
			'before'
		);

		wp_register_style( self::STYLE_HANDLE, RMD_CB_URL . 'assets/css/blocks.css', [], self::version( 'assets/css/blocks.css' ) );
		wp_register_style( self::EDITOR_STYLE, RMD_CB_URL . 'assets/css/editor.css', [], self::version( 'assets/css/editor.css' ) );

		wp_register_script(
			self::PARALLAX_HANDLE,
			RMD_CB_URL . 'assets/js/parallax.js',
			[],
			self::version( 'assets/js/parallax.js' ),
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);

		foreach ( self::NAMES as $name ) {
			register_block_type( RMD_CB_DIR . 'blocks/' . $name );
		}
	}

	/**
	 * The shared editor script also carries the surface panel for the Group block,
	 * so it is loaded wherever the block editor runs.
	 */
	public static function enqueue_editor(): void {
		wp_enqueue_script( self::EDITOR_HANDLE );
	}

	/**
	 * Called by the parallax block on render: loads the movement unless the theme does it.
	 */
	public static function enqueue_parallax(): void {
		if ( current_theme_supports( self::THEME_SUPPORT_PARALLAX ) ) {
			return;
		}
		wp_enqueue_script( self::PARALLAX_HANDLE );
	}

	/**
	 * Data for the editor scripts.
	 *
	 * @return array<string,mixed>
	 */
	public static function editor_config(): array {
		return [
			'surfaces' => Surfaces::config(),
			'parallax' => [
				'shapes'       => Render\ParallaxImage::shapes(),
				'defaultShape' => Render\ParallaxImage::default_shape(),
			],
		];
	}

	/**
	 * Asset version: file modification time, so browsers pick up changes without a version bump.
	 *
	 * @param string $relative Path relative to the plugin folder.
	 */
	public static function version( string $relative ): string {
		$file = RMD_CB_DIR . $relative;
		return is_readable( $file ) ? (string) filemtime( $file ) : RMD_CB_VERSION;
	}
}
