# Security model

This development plugin creates **readable copies of potentially sensitive course data**. A review and passing tests reduce known risks; they do not establish immunity from attacks or constitute an independent penetration test.

## Authorisation and requests

- Login and `local/flatarchive:archive` are required in the course context. Default roles: manager and editingteacher.
- Generation requires POST data and a valid Moodle `sesskey`. The AJAX service uses Moodle external API validation.
- Every requested module must belong to the authorised course, be supported, be user-visible and satisfy native module capabilities. The server repeats checks independently of the selection interface.
- Individual reports are filtered by the exporter's native capabilities, groups, anonymity and review rules. Changes to those checks require regression tests.
- Download tokens contain 192 random bits, are session/user-bound and expire after one hour. Download rechecks course access and archive permission. No client path is used. ZIP symlinks and noncanonical workspace paths are rejected.

## Course report snapshots

Gradebook, completion and participation snapshots have independent native capability checks. They include active enrolled participants in the permitted current course group and can cover activities not selected for content export. Without hidden-grade permission, hidden values and aggregate totals are omitted. Participation excludes raw log payloads and IP addresses; anonymous events require the native permission. All report data becomes part of the downloaded ZIP and has the same confidentiality/retention boundaries as exported activity reports.

## Generated HTML

Moodle text formatting/cleaning and escaped labels provide the primary content boundary. The final generated document pass adds CSP before page content: scripts, objects, frames, network connections from script, base URL overrides and form submissions are prohibited. It removes scripts, handlers, refresh metadata and active forms; external iframes become ordinary links. The policy requires a browser that honours CSP in HTML metadata. Source HTML remains editable by anyone holding the ZIP; “static” is not tamper-proof or cryptographically signed.

Images and audio/video may still load over HTTP(S) to preserve teaching content. Such loads can disclose the reader's IP address to remote providers. External destinations are not downloaded or checked for safety. Original HTML/SVG files opened separately are **not** covered by generated-page CSP. Do not host extracted attachments under the authenticated Moodle origin.

## Attachments and H5P

Original attachments are copied byte-for-byte. The plugin does not scan malware, remove Office macros, sanitise source H5P packages or guarantee safe third-party document viewers. Use the institution's upload/antivirus and document-handling controls. Do not treat a successful export as a malware scan.

H5P conversion does not execute package JavaScript. It rejects unsafe/duplicate paths and ZIP symlinks, caps entries at 20,000 and declared uncompressed size at 2 GiB, caps each JSON document at 16 MiB and nesting at 128, and bounds asset reads to the declared size plus one byte. Only listed passive media extensions are unpacked into browsable asset folders. Original `.h5p` files remain downloadable attachments.

## Operational boundaries

- Keep Moodle, PHP, database, native modules and server libraries patched. This repository does not vendor or audit their complete source trees.
- Archive generation is synchronous. Very large courses, unusually complex content or repeated authorised requests can exhaust PHP time/memory or disk space. There is no global archive size quota or per-user rate limiter in this release. Apply server resource limits and monitor disk usage.
- Moodle's temp directory must be private. Token expiry is not secure erasure. Temporary copies and server backups may outlive the one-hour download window.
- The plugin does not implement a dedicated Moodle Privacy API provider in this release. Its temporary files can contain personal data; deployment must account for temporary storage and retention. No claim of regulatory certification or complete Privacy API coverage is made.
- ZIPs contain no access-control layer, encryption or revocation. Download privileges should be granted only to trusted staff. Restrict storage and redistribution of student records.
- A compromised server, malicious installed PHP plugin, privileged filesystem user, vulnerable browser or attachment viewer is outside the protections supplied here.

## Reporting

Report a suspected vulnerability privately to the repository owner through an available private GitHub security reporting channel or a separately agreed private contact. Do not put student records, tokens, session keys or exploit details for an unpatched vulnerability in a public issue. Include build/Moodle/PHP versions, minimal synthetic reproduction, expected access rights and observed behaviour.

Reference guidance: [Moodle security policy](https://moodledev.io/general/development/policies/security), [CSRF](https://moodledev.io/general/development/policies/security/crosssite-request-forgery), [unauthorised access](https://moodledev.io/general/development/policies/security/unauthorised-access), [CSP](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy).
