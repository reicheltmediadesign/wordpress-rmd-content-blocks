<?php
/**
 * Server-side rendering of rmd/quote-band.
 *
 * @var array<string,mixed> $attributes Block attributes.
 *
 * @package RMD\ContentBlocks
 */

defined( 'ABSPATH' ) || exit;

echo RMD\ContentBlocks\Render\QuoteBand::render( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the render class.
