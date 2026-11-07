<?php
/**
 * Tests for the WordPress-free block migration.
 *
 * @package RMD\ContentBlocks
 */

declare(strict_types=1);

namespace RMD\ContentBlocks\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RMD\ContentBlocks\Domain\BlockMigration;

final class BlockMigrationTest extends TestCase {

	private function migration(): BlockMigration {
		return new BlockMigration(
			[
				'theme/quote'    => [
					'name'       => 'rmd/quote-band',
					'attributes' => static function ( array $attrs ): array {
						$attrs['text'] = strtoupper( (string) ( $attrs['text'] ?? '' ) );
						return $attrs;
					},
				],
				'theme/links'    => [ 'name' => 'rmd/page-links' ],
			]
		);
	}

	private static function block( string $name, array $attrs = [], array $inner = [], string $html = '<div>old</div>' ): array {
		return [
			'blockName'    => $name,
			'attrs'        => $attrs,
			'innerBlocks'  => $inner,
			'innerHTML'    => $html,
			'innerContent' => [ $html ],
		];
	}

	public function test_renames_block_and_drops_saved_markup(): void {
		$result = $this->migration()->migrate( [ self::block( 'theme/links', [ 'items' => [ [ 'pageId' => 5 ] ] ] ) ] );

		$this->assertSame( 1, $result['changed'] );
		$this->assertSame( 'rmd/page-links', $result['blocks'][0]['blockName'] );
		$this->assertSame( [ 'items' => [ [ 'pageId' => 5 ] ] ], $result['blocks'][0]['attrs'] );
		$this->assertSame( '', $result['blocks'][0]['innerHTML'] );
		$this->assertSame( [], $result['blocks'][0]['innerContent'] );
	}

	public function test_applies_attribute_converter(): void {
		$result = $this->migration()->migrate( [ self::block( 'theme/quote', [ 'text' => 'hello' ] ) ] );

		$this->assertSame( 'HELLO', $result['blocks'][0]['attrs']['text'] );
	}

	public function test_migrates_nested_blocks_and_keeps_others(): void {
		$group  = self::block( 'core/group', [ 'className' => 'x' ], [ self::block( 'theme/quote', [ 'text' => 'a' ] ), self::block( 'core/paragraph' ) ] );
		$result = $this->migration()->migrate( [ $group, self::block( 'core/heading' ) ] );

		$this->assertSame( 1, $result['changed'] );
		$this->assertSame( 'core/group', $result['blocks'][0]['blockName'] );
		$this->assertSame( '<div>old</div>', $result['blocks'][0]['innerHTML'] );
		$this->assertSame( 'rmd/quote-band', $result['blocks'][0]['innerBlocks'][0]['blockName'] );
		$this->assertSame( 'core/paragraph', $result['blocks'][0]['innerBlocks'][1]['blockName'] );
		$this->assertSame( 'core/heading', $result['blocks'][1]['blockName'] );
	}

	public function test_freeform_blocks_without_name_are_kept(): void {
		$freeform = self::block( '', [], [], "\n\n" );
		$freeform['blockName'] = null;

		$result = $this->migration()->migrate( [ $freeform ] );

		$this->assertSame( 0, $result['changed'] );
		$this->assertNull( $result['blocks'][0]['blockName'] );
	}

	public function test_mentions_legacy_matches_whole_names_only(): void {
		$migration = $this->migration();

		$this->assertTrue( $migration->mentions_legacy( '<!-- wp:theme/quote {"text":"a"} /-->' ) );
		$this->assertTrue( $migration->mentions_legacy( "<!-- wp:theme/links /-->" ) );
		$this->assertFalse( $migration->mentions_legacy( '<!-- wp:theme/quote-large /-->' ) );
		$this->assertFalse( $migration->mentions_legacy( '<!-- wp:paragraph --><p>theme/quote</p><!-- /wp:paragraph -->' ) );
	}
}
