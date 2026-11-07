# RMD Content Blocks – Projektregeln

WordPress-Plugin von reichelt media.design: Inhaltsblöcke für den Block-Editor (Seitenlinks, Empfehlungen, Parallax-Bild, Zitat-Band) und ein Flächen-Panel für Gruppen. Entstanden aus den Theme-Blöcken von marleninflow.de. Repo: `github.com/reicheltmediadesign/wordpress-rmd-content-blocks`, Lizenz GPL-2.0-or-later, Autor Philipp Reichelt. Aufbau und Release wie `rmd-heading-anchors` (ohne npm/Build-Schritt).

## Aufbau

- `rmd-content-blocks.php` Bootstrap (muss PHP-7-parsebar bleiben), `includes/` PSR-4 `RMD\ContentBlocks\`.
- `blocks/<name>/` je Block: `block.json`, `index.js` (Editor, ohne Build, `wp.*`-Globals), `index.asset.php` (Abhängigkeiten), `render.php` (ruft `Render\<Name>::render()`).
- **Alle Blöcke sind dynamisch** (`save` gibt `null` zurück). Gespeichert wird nur der Block-Kommentar mit Attributen; Markup darf sich ändern, ohne dass Inhalte ungültig werden. Neue Blöcke ebenso bauen.
- `Blocks` registriert die Blöcke, das gemeinsame Editor-Skript `rmd-cb-editor` (`assets/js/editor-shared.js`, Flächen-Panel und Gruppen-Varianten) mit `window.rmdContentBlocks` (Flächen, Parallax-Formen), die Styles `rmd-content-blocks` (`assets/css/blocks.css`, Frontend und Editor) und `rmd-content-blocks-editor` (`assets/css/editor.css`) sowie das Parallax-Skript.
- `Surfaces`: Flächen kommen vom Theme (Filter `rmd_cb_surfaces`), das Plugin bringt nur Panel und Varianten. Ohne Flächen kein Panel.
- `Domain\BlockMigration` ist **WordPress-frei** und wird mit plain PHPUnit getestet; `Migrator` (Beiträge aller Typen außer Revisionen, Block-Widgets) und `Admin\MigrationPage` (Werkzeuge → Inhaltsblöcke, nur mit konfiguriertem Filter `rmd_cb_migrations`) hängen daran.
- Gestaltung nur über `--rmd-cb-*`-Variablen mit Fallback **im `var()`**, nicht auf `:root` (sonst überschreibt das Plugin je nach Ladereihenfolge die Theme-Werte). Neue Variablen im Kopfkommentar von `blocks.css` und in der README-Tabelle nachziehen.

## Code-Regeln

- PHP 8.1+, WP 6.8+. WordPress-Coding-Standards (`phpcs.xml.dist`), Präfix `rmd_cb_` / `RMD_CB_` / Namespace `RMD\ContentBlocks`, Textdomain `rmd-content-blocks`, Blocknamen `rmd/<name>`.
- Quellstrings Englisch, Übersetzung in `languages/rmd-content-blocks-de_DE.po` (Sie-Form) von Hand nachziehen, inklusive der `block.json`-Texte mit `msgctxt` (`block title`, `block description`, `block keyword`). `de_DE_formal` und die JSON-Dateien für die Editor-Skripte erzeugt `bin/make-i18n.sh` (WP-CLI; lokal nicht installiert, im Release-Workflow vorhanden).
- Textdomain in JS-`__()` immer als Literal `'rmd-content-blocks'`.
- Blocknamen und Attributnamen sind gespeicherte Daten: nur mit Migration ändern.
- Version an drei Stellen: Plugin-Header, `RMD_CB_VERSION`, `readme.txt` (Stable tag). `bash bin/check-version.sh` prüft das.

## Build und Prüfung

```powershell
composer phpcs         # PHPCS
composer test          # PHPUnit (Domain)
bash bin/check-version.sh
bash bin/build-zip.sh  # dist/rmd-content-blocks.zip
```

Composer liegt unter `C:\xampp\php\composer.bat`. Lokal testen: `.\bin\link-local.ps1 -WordPressPath "C:\xampp\htdocs\<site>" -Copy`. Kundenseiten bekommen nur das Release-Zip.

## Git und Release

- Committen nur auf Anfrage, auf Feature-Branches; nie direkt auf `main`.
- Release: Versionen an allen Stellen erhöhen, `CHANGELOG.md` und `readme.txt` ergänzen, committen, Tag `vX.Y.Z` pushen. Der Workflow lintet, testet, baut Übersetzungen, erzeugt `rmd-content-blocks.zip` und `update.json` und legt ein **Draft-Release** an. Beide Assets müssen am Release bleiben.
- Den vom Workflow angelegten Entwurf bearbeiten (Releases → Entwurf → Stift), nicht neu anlegen.
- Bei jedem Release Titel `vX.Y.Z – <Kernänderung>` und Release Notes (Englisch, Markdown in Codeblock) mit ausgeben.
- Topics fürs Repo: `wordpress`, `wordpress-plugin`, `gutenberg-blocks`, `block-editor`, `parallax`.
- Nach dem ersten veröffentlichten Release in `rmd-wordpress.git` (`new_project.ps1` u. a.) aufnehmen und den Doku-Block `rmd-docs/blocks/plugins/rmd-content-blocks.md` pflegen.

## README

Nutzerorientiert. Kein Development-Abschnitt in der README; Build/Release steht hier.
