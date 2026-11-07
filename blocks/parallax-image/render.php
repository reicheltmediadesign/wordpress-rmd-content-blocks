<?php
/**
 * Server-side rendering of rmd/parallax-image.
 *
 * @var array<string,mixed> $attributes Block attributes.
 *
 * @package RMD\ContentBlocks
 */

defined( 'ABSPATH' ) || exit;

echo RMD\ContentBlocks\Render\ParallaxImage::render( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the render class.
