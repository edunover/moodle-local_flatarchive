# Architecture — build 2026101405

## Export lifecycle

1. `lib.php` adds course navigation when the archive capability is present. `index.php` authenticates access and renders `templates/index.mustache`.
2. `amd/src/selectors.js` calls `classes/external/get_tree.php` through Moodle `core/ajax`. Names are inserted as text; unsupported modules are disabled. `selection` supplies the same module/capability policy to the UI and server.
3. The form submits course/module IDs and `sesskey` to `archive_process.php`. The endpoint requires login, the course archive capability, a valid session key and POST data. `selection::validate()` checks the entire selection against that course's `course_modinfo`, visibility and module capabilities before creating files.
4. `temporary_archive::create()` allocates an independent random-token workspace in Moodle's temporary directory. Each exporter reads Moodle data through its database/file/native module APIs. There is no archive job database or background worker.
5. The endpoint delegates to activity exporters (Page and Book are rendered directly). Exporters return rendered index items and generated filenames. Moodle file-area content is copied to isolated `files/<cmid>/<area>/<itemid>/` paths.
6. `settings_snapshot::add()` writes permitted configuration pages and links; `index_icons::add()` copies installed Moodle icons. `local_flatarchive_group_index_links()` orders the course index by actual sections.
7. `reports::write()` adds a local report index, moves report sections of successful activity exports to dedicated activity report pages, and delegates capability-gated course snapshots to `course_reports`. Then `layout::organise()` remaps every entry except `index.html` below `resources/`, rewrites known local URLs and invokes `html_safety::harden()` on generated HTML. Original file attachments are deliberately excluded from this transformation.
8. Moodle's ZIP packer builds `archive.zip`. Only a successful build is registered in the user's session. An exception deletes the failed workspace.
9. `serve_temp.php` rechecks login, session key, archive ownership/expiry and course capability before streaming the ZIP with private/no-store and nosniff headers.

## Code map

| File/class | Responsibility and contract |
|---|---|
| `selection` | `supported(type)`, `permitted(cm)`, `validate(modinfo, ids)`: shared selection policy; no file writes. |
| `temporary_archive` | `create()`, `register(token, course, name)`, `resolve(token)`: random workspace identity, session metadata and canonical path checks. Expired session entries are pruned on registration. |
| `paths` | `stem()` gives generated Page/Book names a unique identity suffix; `assets()` isolates file areas; `relative()` rejects traversal/absolute/control-character paths. |
| `locallib.php` | Visibility badges, version label, installed module icons, Moodle file-area copying/link rewriting, checked writes, ordered section grouping. |
| `archive_templates/{index,page,book_toc}_template.php` | Document shells. Titles are escaped; body HTML must already have been formatted by the caller. Final security policy is added during archive layout finalisation. |
| `reports`, `course_reports` | Local report index, participant roster and four read-only course snapshots. Participants and Gradebook link directly from the course navigation, before Reports. Capabilities are checked before each report reads data; group and participant filters apply. The fixed report marker identifies the section to move from the registered main document. Activity navigation links Settings and Reports side by side; the course report index excludes activity reports. |
| `layout` | ZIP destination mapping and relative HTML/CSS/srcset URL rewriting. Only known archive entries are remapped. |
| `html_safety` | Final browser CSP, removal of active elements/handlers/forms, disabled controls, external iframe-to-link conversion and referrer protection. It supplements Moodle cleaning; it is not an independent full HTML sanitiser. |
| `settings_snapshot`, `data_settings` | Whitelisted course/module/database-field settings, descriptions, tags, local links. Values representing passwords are replaced with presence indicators. |
| `tags`, `index_icons` | Native Moodle tags and module icons. No external icon CDN. |
| `*_exporter` | Module-specific readable data selection, conversion and files. Public `render(cm, modinfo, directory)` writes an activity snapshot. Resource/Label return a rendered index item; Folder returns item/filename; document exporters return item/filenames. |
| `quiz_question` | Native question rendering, conversion of interactive controls to reading output and permitted question-file copying. |
| `lesson_attempts` | Recorded attempts, retries, stored responses and scores; no grade recomputation. |
| `h5p_package` | Bounded ZIP metadata/JSON reading, path and symlink checks, passive asset copying, original package preservation. |
| `h5p_renderer` | Supported H5P JSON to static HTML; no execution of package libraries. Unsupported content is signalled. |
| `db/access.php`, `db/services.php` | Course capability and authenticated read-only AJAX service declarations. |
| `db/upgrade.php` | One necessary migration removing the obsolete prototype job table from existing installations. No old export engine remains in it. |
| `styles.css`, `style/moodle.css` | Moodle selection styling and standalone archive styling respectively. The latter is a small local stylesheet, not a complete Moodle theme. |
| `lang/en`, `lang/fr`, `lang/nl` | Plugin interface and archive strings. Moodle core/module translations remain runtime dependencies. |
| `scripts/build-amd.cjs` | Terser build of the named Moodle AMD module and source map. |
| `scripts/package-plugin.py` | ZIP from a positive list of tracked runtime/documentation paths; rejects symlinks and non-files; verifies required entries and ZIP integrity. |

## Access and reporting

The global course archive permission does not replace native permissions. `selection::CAPABILITIES` is the definitive list of module prerequisites. Lesson additionally requires edit permission; Database requires manageentries; H5P and Workshop require manageactivities. Settings require course update or activity management as appropriate.

Exporters apply additional checks before including reports: Feedback viewreports; Choice publication/report/download rules; Quiz management, report and review rules; Lesson report access; Assignment grading, group and anonymity rules; Workshop submission/assessment/example/name permissions; H5P native report access. Forum and Wiki use native visibility logic. These checks must remain close to data reads: adding an exporter to the UI is not sufficient to authorise its reports.

Database export intentionally includes unapproved entries for authorised entry managers and bypasses the minimum-contributions viewing threshold. Glossary export remains approved-only. These are distinct policies, not a generic rule applied to all record-like modules.

## Data and storage

There are no plugin-owned persistent tables for new installations. The upgrade removes the old job table. Session metadata contains user ID, course ID, safe download filename and expiry; the 24-byte random token identifies the workspace. Generated files may contain personal data from the course. Temporary files are under `$CFG->tempdir/flatarchive`, outside the public web root in a correctly configured Moodle installation.

One hour is the download authorisation lifetime, not a physical deletion deadline. Moodle's temp cleanup and local backups determine actual retention. A downloaded copy is independent of Moodle and cannot be revoked. Filesystem administrators and server backups remain outside the plugin's access-control boundary.

## Extension rules

For a new module: define its permission policy, implement a bounded renderer using native access APIs, format/clean text with the correct context, isolate file areas, register supported settings, add translations and regression fixtures, and run the complete matrix. Never execute attachment code, trust a submitted path, copy arbitrary configuration records, or assume UI disabling is authorisation.

The executable prototype (PDF pipeline, custom AJAX polling, job processing and bundled dependencies) is not part of the current runtime. Useful shared HTML helpers are retained and tested. Historical Git commits are not rewritten.

After registration, `archive_process.php` redirects to `download.php?token=…` (POST/Redirect/GET). `download_page` rechecks session ownership, expiry and archive capability on each visit. The canonical page URL retains the token for Moodle language navigation; viewing it needs no generation POST or CSRF token. Downloading still uses `serve_temp.php` with its existing sesskey check.

`appearance::write()` snapshots site-level custom CSS into `shared/institution.css`. After URL rewriting and HTML hardening, `appearance::decorate()` adds styling classes and a relative link to this local asset. Attachments remain excluded. `settings.php` and the appearance AMD module provide explicit preset selection and an editable admin CSS field.
