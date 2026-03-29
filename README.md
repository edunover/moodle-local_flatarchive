# local_flatarchive

Moodle local plugin — Archive course resources (Pages & Books) as self-contained static HTML pages bundled in a downloadable ZIP.

**Version:** 0.2.0 (2026032903) · MATURITY_ALPHA · Requires Moodle 4.4+

---

## What it does

Flatarchive lets teachers export selected course resources to a portable offline archive:

- Selects **Page** and **Book** activities from the course tree
- Generates a **static HTML website** — no Moodle dependency after download
- Rewrites all `@@PLUGINFILE@@` links to relative paths and copies embedded files
- Adds **visibility badges** (hidden / stealth / hidden section) on each page and in the index
- Includes **module icons** (Moodle 4.x `monologo.svg`) in the index for quick identification
- Packages everything in a **ZIP** named `archive_{shortname}_{YYYYMMDDHHmm}.zip`
- Footer on every page: `Flatarchive {version} © EDUNOVER`

### Archive structure

```
archive_SHORTNAME_202603291200.zip
├── index.html              ← course index with all resources
├── about.html              ← course description
├── shared/
│   ├── moodle.css          ← standalone CSS
│   └── icons/
│       ├── page.svg
│       └── book.svg
├── Page title.html         ← one file per Page activity
├── Book title/
│   ├── index.html          ← book table of contents
│   ├── Chapter one.html
│   └── Chapter two.html
└── files/
    └── {cmid}/             ← embedded images & files
```

---

## Installation

1. Copy the plugin folder to `{moodle_root}/local/flatarchive/`
2. Install Composer dependencies:
   ```bash
   cd local/flatarchive
   composer install --no-dev
   ```
3. Visit **Site administration → Notifications** to run the DB install
4. Assign the capability `local/flatarchive:archive` to the Teacher role

> `vendor/` is git-ignored — always run `composer install` on a fresh server.

---

## Usage

1. Open a course → the **Flat archive** link appears in the course navigation
2. Select the Pages and/or Books to archive
3. Click **Générer l'archive HTML**
4. Download the ZIP from the success page

---

## Requirements

| Requirement | Version |
|---|---|
| Moodle | 4.4+ |
| PHP | 7.4+ |
| mPDF (via Composer) | 8.2.7 |

---

## Supported resource types

| Type | Support |
|---|---|
| `mod_page` | ✅ Full |
| `mod_book` | ✅ Full (TOC + chapters) |
| `mod_resource` | Planned |
| `mod_url` | Planned |
| `mod_label` | Planned |

---

## Capability

| Capability | Default role |
|---|---|
| `local/flatarchive:archive` | Teacher, Manager |

---

## Known limitations

- Archive generation is **synchronous** — very large courses may hit PHP `max_execution_time` on shared hosting
- MySQL `wait_timeout=60s` (LWS) mitigated by keepalive queries between modules
- JS interface uses a non-AMD script (AMD migration pending)

---

## License

GNU GPL v3 or later — http://www.gnu.org/copyleft/gpl.html

© 2025 [Edunover](https://edunover.eu)
