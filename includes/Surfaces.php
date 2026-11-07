<?php
/**
 * Surfaces: coloured section backgrounds for the Group block and the quote band.
 *
 * The plugin brings the panel (block sidebar "Surface") and the Group
 * variations; the surfaces themselves are classes the theme defines and
 * styles. A theme registers them with the filter "rmd_cb_surfaces":
 *
 *     add_filter( 'rmd_cb_surfaces', function ( $config ) {
 *         $config['surfaces'][]  = [ 'class' => 'surface-mint', 'label' => 'Mint' ];
 *         $config['modifiers'][] = [ 'class' => 'surface--lines', 'label' => 'Lines', 'help' => '…' ];
 *         $config['quote_default'] = 'surface-mint';
 *         return $config;
 *     } );
 *
 * Without surfaces the panel stays hidden and the quote band uses its plain style.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks;

defined( 'ABSPATH' ) || exit;

final class Surfaces {

	/** @var array<string,mixed>|null Configuration of this request. */
	private static ?array $config = null;

	/**
	 * Surfaces as configured by the theme, sanitized. Read once per request.
	 *
	 * @return array{surfaces:array<int,array{class:string,label:string}>,modifiers:array<int,array{class:string,label:string,help:string}>,variations:array<int,array{name:string,title:string,class:string}>,quoteDefault:string,helpOn:string,helpOff:string}
	 */
	public static function config(): array {
		if ( null !== self::$config ) {
			return self::$config;
		}

		$raw = apply_filters(
			'rmd_cb_surfaces',
			[
				'surfaces'      => [],
				'modifiers'     => [],
				'variations'    => null,
				'quote_default' => '',
				'help_on'       => '',
				'help_off'      => '',
			]
		);
		$raw = is_array( $raw ) ? $raw : [];

		$surfaces = self::entries( $raw['surfaces'] ?? [], false );

		$variations = [];
		if ( isset( $raw['variations'] ) && is_array( $raw['variations'] ) ) {
			foreach ( $raw['variations'] as $variation ) {
				if ( ! is_array( $variation ) || empty( $variation['class'] ) || empty( $variation['title'] ) ) {
					continue;
				}
				$class        = self::classes( (string) $variation['class'] );
				$variations[] = [
					'name'  => sanitize_key( (string) ( $variation['name'] ?? $class ) ),
					'title' => (string) $variation['title'],
					'class' => $class,
				];
			}
		} else {
			foreach ( $surfaces as $surface ) {
				$variations[] = [
					'name'  => sanitize_key( $surface['class'] ),
					/* translators: %s: surface name, e.g. "Mint" */
					'title' => sprintf( __( 'Surface: %s', 'rmd-content-blocks' ), $surface['label'] ),
					'class' => $surface['class'],
				];
			}
		}

		self::$config = [
			'surfaces'     => $surfaces,
			'modifiers'    => self::entries( $raw['modifiers'] ?? [], true ),
			'variations'   => $variations,
			'quoteDefault' => self::classes( (string) ( $raw['quote_default'] ?? '' ) ),
			'helpOn'       => (string) ( $raw['help_on'] ?? '' ),
			'helpOff'      => (string) ( $raw['help_off'] ?? '' ),
		];

		return self::$config;
	}

	/**
	 * Surface classes of a quote band: its own className, else the theme default.
	 *
	 * @param string $class_name Block className attribute.
	 */
	public static function quote_classes( string $class_name ): string {
		$class_name = self::classes( $class_name );
		if ( self::has_surface( $class_name ) ) {
			return $class_name;
		}

		$default = self::config()['quoteDefault'];
		return trim( $class_name . ' ' . $default );
	}

	/**
	 * Whether a class list contains one of the configured surfaces.
	 *
	 * @param string $class_name Class list.
	 */
	public static function has_surface( string $class_name ): bool {
		$tokens = preg_split( '/\s+/', trim( $class_name ) ) ?: [];
		foreach ( self::config()['surfaces'] as $surface ) {
			if ( in_array( $surface['class'], $tokens, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param mixed $entries   Raw list.
	 * @param bool  $with_help Keep a help text per entry.
	 * @return array<int,array<string,string>>
	 */
	private static function entries( $entries, bool $with_help ): array {
		$out = [];
		foreach ( is_array( $entries ) ? $entries : [] as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['class'] ) || empty( $entry['label'] ) ) {
				continue;
			}
			$item = [
				'class' => sanitize_html_class( (string) $entry['class'] ),
				'label' => (string) $entry['label'],
			];
			if ( $with_help ) {
				$item['help'] = (string) ( $entry['help'] ?? '' );
			}
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * Sanitizes a space separated class list.
	 *
	 * @param string $classes Class list.
	 */
	public static function classes( string $classes ): string {
		$tokens = array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', trim( $classes ) ) ?: [] ) );
		return implode( ' ', array_unique( $tokens ) );
	}
}
