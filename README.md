# Flat Archive for Moodle

`local_flatarchive` creates a portable, static HTML snapshot of selected course content. Extract the ZIP and open `index.html`; all other pages and assets are under `resources/`. It is a reading archive, **not a Moodle backup or restore format**.

Development release: **0.2.0 / build 2026101304**, maturity **alpha**. Minimum Moodle: **4.4**. The CI configuration covers Moodle 4.4–5.3 across their configured PHP versions; a configured matrix is not evidence that every run passed. See the [test runs](https://github.com/edunover/moodle-local_flatarchive/actions/workflows/tests.yml).

## Installation and use

1. Install the release ZIP using Moodle's plugin installer, or place its `flatarchive` directory in `local/flatarchive` and complete the Moodle upgrade.
2. Open a course → More → Flat archive. Managers and editing teachers receive `local/flatarchive:archive` by default.
3. Select accessible supported activities and generate the archive. Unsupported activities remain visible but cannot be selected.
4. Download the ZIP. Repeated downloads are available for one hour in the same authenticated session. Temporary disk files are subsequently reclaimed by Moodle's temporary-file cleanup; expiry does not guarantee immediate physical erasure.
5. Extract the entire ZIP and open `index.html`. Keep `resources/` alongside it.

There is **no Composer installation, mPDF, Node.js, CDN or JavaScript player required to read the generated pages**. Node.js and Composer are used only by development/test tooling. The plugin's Moodle selection interface uses Moodle's AMD JavaScript loader.

## Supported activities and resources

| Moodle module | Archived material and principal limits |
|---|---|
| Page (`page`) | Formatted content and embedded Moodle files. |
| Book (`book`) | Contents page and chapter pages; hidden chapters require the native viewing permission. |
| Text and media (`label`) | Inline content and media in the course index. |
| File (`resource`) | Main file and companion files, preserving original bytes. |
| Folder (`folder`) | Navigable directory tree and original files. |
| URL (`url`) | Description and external link; the destination website is not downloaded. |
| Glossary (`glossary`) | Approved entries, aliases, categories, authors, readable comments and attachments. Pending entries and ratings are excluded. |
| Wiki (`wiki`) | Latest readable page revisions, local links, files, authors and readable comments. No revision history. |
| Feedback (`feedback`) | Questionnaire and permitted submitted individual responses. No drafts; anonymous responses omit identity and date. |
| Choice (`choice`) | Options and results permitted by publication settings, report permissions and group scope. |
| Forum (`forum`) | Accessible discussions/messages, pin status, nested replies, authors and attachments. |
| Lesson (`lesson`) | Teaching content, answers/solutions and accessible attempt reports; edit permission required. Dynamic navigation is described. |
| Quiz (`quiz`) | Questionnaire/solutions for authorised managers and attempts accessible under Moodle review/report rules. No new random draw or grading. |
| Database (`data`) | List and single-record templates, files, approved and unapproved records with badges. Requires entry management; minimum contribution visibility threshold is documented rather than applied. Custom JavaScript is omitted. |
| Assignment (`assign`) | Instructions, supported submissions, stored grades, feedback text/files. No advanced grading forms or PDF annotation rendering. |
| H5P (`h5pactivity`) | Static Course Presentation, Interactive Video, Question Set, Drag and Drop, Drag the Words; supported nested elements, solutions and available recorded reports. Original `.h5p` retained. No interactive player. `mod_hvp` is not supported. |
| Workshop (`workshop`) | Instructions, native assessment forms, examples and permitted submissions/assessments/grades. Supports accumulative, comments, number-of-errors and rubric strategies. Stored scores are not recalculated. |

Course/activity settings are **documentary snapshots of explicitly selected fields**, not complete configuration dumps. Descriptions (including empty fields), visibility, activity tags and supported settings are included. Password values, secrets, user overrides and arbitrary third-party configuration are excluded. Course/content tags are retained where supported. Every generated content page has a route back to the course index.

Exports respect current Moodle capabilities, visibility and the exporter-specific reporting rules. They can contain student names, submissions and grades. An exported ZIP has no Moodle access control or revocation mechanism: store and share it accordingly.

## Static content and security boundaries

Generated HTML is cleaned using Moodle APIs and finalised with a restrictive Content Security Policy. Scripts and forms cannot be used as interactive activities; embedded external applications become links. Native image/audio/video playback remains possible. Remote media and external links may require Internet access.

Original attachments, including HTML/SVG/Office documents and source H5P packages, are preserved rather than disinfected. They are outside the generated-page policy. This plugin is not an antivirus, a content sandbox or a guarantee against every attack. Read [SECURITY.md](SECURITY.md) before deployment.

The planned multilingual archive navigation for `{mlang}` and FilterCodes `{multilang xx}` remains deferred. No claim is made that all third-party question types, field plugins or embedded applications can be rendered faithfully.

## Development and verification

```sh
npm ci
npm test
npm run build
git diff --exit-code -- amd/build
phpcs --standard=.phpcs.xml --extensions=php .
python3 scripts/package-plugin.py --output /tmp/local_flatarchive.zip
```

PHPCS must have `moodle-extra` installed. PHPUnit needs a separate Moodle test installation and database: use the workflow in `.github/workflows/tests.yml` or `moodle-plugin-ci phpunit --fail-on-warning` in its configured environment. Never point PHPUnit at a production database. The package builder includes tracked runtime/documentation files only: stage new files before packaging.

- [Architecture and code responsibilities](docs/ARCHITECTURE.md)
- [Runtime, development and CI dependencies](docs/DEPENDENCIES.md)
- [Security model and reporting](SECURITY.md)
- [2026-10-13 review and verification record](docs/AUDIT-2026-10-13.md)

## Français

Flat Archive produit une copie HTML consultable hors Moodle. Le ZIP contient uniquement `index.html` et le dossier `resources/` à sa racine. Ce n'est pas une sauvegarde restaurable. Les activités deviennent des documents de consultation ; aucune réponse ni note n'est recalculée. Les droits Moodle contrôlent l'export, mais ne protègent plus le ZIP une fois téléchargé. Les pièces jointes originales ne sont pas désinfectées. La documentation technique et les limites de sécurité ci-dessus font partie de la livraison.

## Licence

Code du plugin : GNU GPL v3 ou ultérieure, Edunover. Les contenus de cours et pièces jointes conservent leurs propres droits. Les icônes sont copiées depuis les modules Moodle installés. Voir les licences individuelles des outils de développement dans le fichier de verrouillage npm et leurs distributions.
