# Changelog

All notable changes to this plugin are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [0.1.0] – 2026-11-07

### Added

- Block **Page links** (`rmd/page-links`): up to three cards to pages or external sites; featured image and main-menu label of the target page, both overridable; optional anchor.
- Block **Recommendations** (`rmd/recommendations`): cards for external offers with affiliate disclosure (badge, `rel="sponsored"`, notice below the cards, own notice text per block, filter `rmd_cb_recommendations_notice`); highlighted card across the whole row.
- Block **Parallax image** (`rmd/parallax-image`): heights small/medium/large, shapes straight/arch/round (filter `rmd_cb_parallax_shapes`, `rmd_cb_parallax_default_shape`), motion soft/strong, sentence on top. Own movement script, off with `prefers-reduced-motion` or `add_theme_support( 'rmd-content-blocks-parallax' )`.
- Block **Quote band** (`rmd/quote-band`): sentence and source on a surface or a plain band.
- **Surface** panel for the Group block and the quote band, Group variations in the inserter; surfaces come from the theme (filter `rmd_cb_surfaces`).
- **Migration** under Tools → Content blocks for blocks a theme used to register (filter `rmd_cb_migrations`), covering posts of every type and block widgets; posts keep a revision.
- Styling through `--rmd-cb-*` custom properties with neutral fallbacks.
- RMD Focal Zoom Point panel for the parallax image.
- German translation (formal).
