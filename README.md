# RMD Content Blocks

Content blocks for the WordPress block editor: page links, recommendations with affiliate disclosure, a parallax image and a quote band, plus a "Surface" panel that puts groups on a coloured background. All blocks are rendered on the server, so saved content stays valid when the markup changes; themes adapt the look through CSS custom properties.

## Features

- **Page links** – up to three cards linking to other pages or external sites. Image and label come from the target page (featured image, label in the main menu) and update by themselves; both can be overridden. Optional anchor to jump to a section of the target page.
- **Recommendations** – cards with image, title, text and a button to someone else's offer. With *Affiliate links* on (default), every card is marked as advertising, every link gets `rel="sponsored"`, and a disclosure appears below the cards. One card can be highlighted across the whole row.
- **Parallax image** – a picture between two sections that drifts slower than the page while scrolling, in three heights, two motion strengths and the shapes *straight*, *arch* and *round* (themes can add more). Optional sentence on top. No movement with *prefers-reduced-motion*.
- **Quote band** – one sentence in large, light type on a coloured band across the full width, with an optional source.
- **Surfaces** – a *Surface* panel in the sidebar of the Group block and the quote band, plus Group variations in the inserter. The surfaces themselves come from the theme.
- **Migration** – *Tools → Content blocks* replaces blocks a theme used to register with these blocks, keeping their settings and texts. Each post keeps its previous version as a revision.
- **RMD Focal Zoom Point** – the parallax image gets the image section & zoom panel; card images use the focal point automatically.

## Usage

Insert the blocks from the *Design* category of the block inserter. Cards and settings are edited in the block sidebar; the canvas shows the same rendering as the site.

The *Surface* panel and the Group variations only appear once the theme registers surfaces (see below).

## Styling with CSS custom properties

The plugin brings layout and behaviour; colours, fonts and radii are custom properties with neutral fallbacks. Set them on `:root` in the theme stylesheet and on `.editor-styles-wrapper` (or `:root`) in the editor stylesheet:

| Property | Used for | Fallback |
|---|---|---|
| `--rmd-cb-gap` | Gap between cards | `25px` |
| `--rmd-cb-radius` | Card corners | `16px` |
| `--rmd-cb-box-radius` | Corners of the page links box | `24px` |
| `--rmd-cb-card-bg` | Card background | `#fff` |
| `--rmd-cb-box-bg` | Background of the page links box | `#f3f4f4` |
| `--rmd-cb-shadow` | Shadow colour as `r, g, b` | `0, 0, 0` |
| `--rmd-cb-heading-font` / `--rmd-cb-heading-color` | Card titles, page link labels | `inherit` |
| `--rmd-cb-accent` | Hover arrow, highlight outline | `currentColor` |
| `--rmd-cb-muted` | Affiliate badge and notice | `#646970` |
| `--rmd-cb-quote-font` / `--rmd-cb-quote-weight` / `--rmd-cb-quote-color` | Quote band text | `inherit` / `300` / `inherit` |
| `--rmd-cb-quote-bg` | Quote band without a surface | `#f3f4f4` |
| `--rmd-cb-text-width` | Width of quote and image text | `820px` |
| `--rmd-cb-inset-width` / `--rmd-cb-inset-radius` | Parallax shape *round* | `1120px` / `36px` |
| `--rmd-cb-parallax-bg` | Parallax frame while the picture loads | `#eee` |
| `--rmd-cb-parallax-font` | Sentence on the parallax image | heading font |
| `--rmd-cb-on-image` | Text colour on images | `#fff` |

Main classes: `.rmd-page-links-box`, `.rmd-page-links__item`, `.rmd-recommendations__item`, `.rmd-quote`, `.rmd-parallax` (with `--small|--medium|--large`, `--<shape>`, `--has-text`). The full list is in the header of `assets/css/blocks.css`.

## For theme developers

- **Surfaces:** filter `rmd_cb_surfaces` with `surfaces` (`class`, `label`), `modifiers` (`class`, `label`, `help`), optional `variations` (`name`, `title`, `class`), `quote_default` (classes for new quote bands), `help_on` / `help_off`. The theme styles the classes.
- **Parallax shapes:** filter `rmd_cb_parallax_shapes` (`value`, `label`) and `rmd_cb_parallax_default_shape`; the block gets the class `rmd-parallax--<value>`.
- **Own parallax movement:** `add_theme_support( 'rmd-content-blocks-parallax' )` keeps the plugin script off; move `.rmd-parallax__img` yourself (`data-parallax-speed` on the figure).
- **Disclosure text:** filter `rmd_cb_recommendations_notice`; a text in the block wins.
- **Menu for page link labels:** filter `rmd_cb_page_links_menu_location`, otherwise the first menu location with a menu.
- **Migration:** filter `rmd_cb_migrations`, legacy block name => `[ 'name' => 'rmd/…', 'attributes' => callable ]`. The callable converts the attributes; the saved markup of the legacy block is dropped.

## Privacy

The plugin stores no personal data, sets no cookies and loads nothing from external servers.

## Installation

1. Download `rmd-content-blocks.zip` from the [latest release](https://github.com/reicheltmediadesign/wordpress-rmd-content-blocks/releases/latest).
2. Upload it under *Plugins → Add New → Upload Plugin* and activate it.
3. Updates appear in the WordPress dashboard like those of any other plugin.

## License

GPL-2.0-or-later. © Philipp Reichelt, reichelt media.design.
