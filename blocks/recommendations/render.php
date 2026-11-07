<?php
/**
 * Server-side rendering of rmd/recommendations.
 *
 * @var array<string,mixed> $attributes Block attributes.
 *
 * @package RMD\ContentBlocks
 */

defined( 'ABSPATH' ) || exit;

echo RMD\ContentBlocks\Render\Recommendations::render( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the render class.
