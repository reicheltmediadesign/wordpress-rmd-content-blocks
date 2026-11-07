<?php
/**
 * Rewrites parsed blocks (the arrays of parse_blocks()) from legacy block types
 * to the blocks of this plugin. WordPress-free, so it is unit tested.
 *
 * A map entry looks like:
 *
 *     'theme/quote' => [
 *         'name'       => 'rmd/quote-band',
 *         'attributes' => static fn ( array $attrs ): array => $attrs, // optional
 *     ]
 *
 * The target blocks are dynamic, so the saved markup of the legacy block is
 * dropped: the migrated block keeps only its comment with the attributes.
 *
 * @package RMD\ContentBlocks
 */

namespace RMD\ContentBlocks\Domain;

final class BlockMigration {

	/**
	 * @param array<string,array{name:string,attributes?:callable}> $map Legacy name => target.
	 */
	public function __construct( private array $map ) {}

	/**
	 * Legacy block names this migration knows.
	 *
	 * @return string[]
	 */
	public function legacy_names(): array {
		return array_keys( $this->map );
	}

	/**
	 * Migrates a list of parsed blocks, including nested ones.
	 *
	 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
	 * @return array{blocks:array<int,array<string,mixed>>,changed:int}
	 */
	public function migrate( array $blocks ): array {
		$changed = 0;

		foreach ( $blocks as $index => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$inner                           = $this->migrate( $block['innerBlocks'] );
				$blocks[ $index ]['innerBlocks'] = $inner['blocks'];
				$changed                        += $inner['changed'];
			}

			$name = $block['blockName'] ?? null;
			if ( ! is_string( $name ) || ! isset( $this->map[ $name ] ) ) {
				continue;
			}

			$target = $this->map[ $name ];
			$attrs  = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
			if ( isset( $target['attributes'] ) && is_callable( $target['attributes'] ) ) {
				$attrs = (array) call_user_func( $target['attributes'], $attrs );
			}

			$blocks[ $index ] = [
				'blockName'    => $target['name'],
				'attrs'        => $attrs,
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			];
			++$changed;
		}

		return [
			'blocks'  => $blocks,
			'changed' => $changed,
		];
	}

	/**
	 * Whether serialized content may contain a legacy block (cheap pre-check before parsing).
	 *
	 * @param string $content Post content.
	 */
	public function mentions_legacy( string $content ): bool {
		foreach ( $this->legacy_names() as $name ) {
			// The name must end at whitespace: "theme/quote" must not match "theme/quote-large".
			if ( preg_match( '/<!-- wp:' . preg_quote( $name, '/' ) . '\s/', $content ) ) {
				return true;
			}
		}
		return false;
	}
}
