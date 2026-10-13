# Dependencies — reviewed 2026-10-13

## Production runtime

| Dependency | Use | Version/source and boundary |
|---|---|---|
| Moodle core | Login, capabilities, sessions, contexts, external API, database queries, File API, formatting/cleaning, language strings, ZIP packer, temporary storage and cleanup | Minimum `2024042200` (4.4); supplied and maintained by the Moodle site. |
| Native Moodle modules | Their data schemas, access/report APIs, renderers and icons | Must match the installed Moodle release. The 17 supported module types are listed in README and `selection`. |
| Moodle question engine | Quiz question/attempt rendering and file permissions | Supplied by Moodle; arbitrary third-party question types are not guaranteed. |
| Moodle HTMLPurifier integration | Safe Database template rendering and formatted content | Moodle-owned dependency; not independently bundled here. |
| PHP | Plugin execution | CI: 8.1–8.4, paired with compatible Moodle branches. Use the requirements of the installed Moodle version. |
| PHP DOM/libxml | HTML parsing, link rewriting and final document hardening | PHP extensions; parsers explicitly disable network access with `LIBXML_NONET`. |
| PHP ZipArchive/libzip | Bounded H5P ZIP inspection and media reads | `zip` extension; update through the PHP/server distribution. |
| PHP JSON, streams, filesystem and cryptographic randomness | Bounded JSON decoding, checked copies, random workspace tokens | Standard PHP APIs; no subprocess execution by the export runtime. |
| Local CSS | Archive appearance | `style/moodle.css`, maintained in this repository; no CSS framework runtime dependency. |
| Moodle AMD loader, `core/ajax`, `core/notification`, `core/str` | Selection interface inside Moodle | Browser-side Moodle dependencies only; not shipped as an archive player. |

**No production Composer dependencies, mPDF, `vendor/`, npm modules or H5P player libraries are installed by this plugin.** Uploaded `.h5p` packages and ordinary attachments are content, not trusted plugin dependencies. H5P library code is not executed by the exporter.

## Development dependencies

`package-lock.json` is authoritative for all direct/transitive npm versions, registry URLs and integrity hashes. Install with `npm ci`, not an unconstrained update.

| Direct dependency | Locked version at review | Purpose |
|---|---|---|
| `jsdom` | 26.1.0 | DOM tests for the Moodle selection interface; never included in the plugin ZIP. |
| `terser` | 5.51.2 | Build the committed AMD JavaScript and source map; never required by Moodle/PHP. |
| Node.js | 22 in CI | Run tests and build. |
| Python | 3.9+ | Release packaging, standard library only (`pathlib`, `subprocess`, `zipfile`, `hashlib`, `argparse`). |

`npm audit --json` on 2026-10-13 reported zero known vulnerabilities for the lockfile (50 dependency entries reported by npm). This is a point-in-time advisory lookup, not proof of absence of vulnerabilities. [npm inventory](DEPENDENCIES-NPM.json) records locked package versions, integrity and declared licence fields where present. Recreate this inventory when changing the lockfile.

## CI-only dependencies

- `moodlehq/moodle-plugin-ci` **4.5.9**, installed with Composer in the isolated CI job. Its own distribution resolves PHP tooling dependencies. This repository does not lock the complete transitive Composer graph; job output and the installed `ci/composer.lock` describe a particular run.
- Moodle coding standards (`moodle-extra`) through the test tools. Local review used `moodlehq/moodle-cs` 3.7.0. The full PHP tree is now checked, not a curated list of recent files.
- PHPUnit and Moodle test libraries are supplied by the isolated Moodle test installation.
- Ubuntu 24.04 GitHub runner and MariaDB container: 10.11, or 11.4 for Moodle 5.3. OS packages and container minor tags are mutable external dependencies, not fully reproducible images.
- Moodle source branches `MOODLE_404_STABLE` through `MOODLE_503_STABLE` track upstream changes; runs intentionally test current branch heads.

GitHub Actions are pinned to verified commit IDs:

| Action | Commit |
|---|---|
| `actions/checkout` (v6) | `d23441a48e516b6c34aea4fa41551a30e30af803` |
| `actions/setup-node` (v4) | `49933ea5288caeca8642d1e84afbd3f7d6820020` |
| `shivammathur/setup-php` (v2) | `eb7c497e18156a6bbabfef1d3a82760b9eda3962` |

The workflow uses read-only repository permission and disables persisted checkout credentials. Dependabot is configured for weekly npm and GitHub Actions update proposals. This does not update Moodle, PHP, MariaDB or Composer tooling automatically.

## Release contents

The package builder uses a positive list: runtime PHP, AMD assets, templates, local styles, language packs, database declarations/migration, README, security documentation and `docs/`. It excludes tests, fixtures, scripts, CI configuration, npm metadata, `node_modules`, Composer/vendor files and old custom AJAX endpoints. Symlinked files are rejected. No network call is needed to package tracked files.

A source checkout is not itself an installable release ZIP. The builder validates ZIP integrity and required entries and prints SHA-256. A checksum detects accidental change only when obtained from a trusted source; it is not a digital signature.
