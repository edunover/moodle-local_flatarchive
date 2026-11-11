# Test guide — Flat Archive 0.3.0-beta.2

> **Beta — testing only.** Provided as is, without warranty to the extent permitted by applicable law. Use a test instance and synthetic or anonymised data; back up before installation and verify generated archives. Liability limitations do not exclude mandatory legal rights. [FR/EN](BETA_WARNING.md).

[Français](BETA_TESTING.fr.md) · [README](../README.md)

Build **2026111106**, maturity **Beta**. This second beta includes the corrections from issues #1–#6 and requests targeted LaTeX and Compilatio validation. The prerelease is public; invitations organise feedback rather than technically restricting downloads.

## Prepare your environment

Use a test instance, dummy accounts and synthetic or anonymised data. Back up the instance before installation or upgrade. Record exact Moodle, PHP and plugin versions, theme, course format and relevant plugins. The declared minimum is Moodle 4.4; the repository workflow defines the automated matrix.

Download `local_flatarchive-0.3.0-beta.2.zip` from the GitHub prerelease. Check its integrity against `SHA256SUMS.txt` using `shasum -a 256 local_flatarchive-0.3.0-beta.2.zip` on macOS or `sha256sum` on Linux. Compare the result to the published file. This is not a digital signature.

Archives may contain personal data. Never attach real archives, student names, grades, submissions, passwords, tokens or session keys to public issues. Do not serve archives under Moodle's authenticated origin.

## Targeted beta.2 checks

- **LaTeX — issue #6:** use the original content from the report, with inline and display formulas in a Page, a text/media area and an activity description. Export with and without Settings, then inspect the extracted archive offline. Common notation should render as static MathML; unsupported notation must retain the full source with a notice. Report browser/version and a non-sensitive formula if the result differs. M01 passed the maintainer's Moodle 4.5 checks with and without Settings on the fix.6 build; confirmation on the reporter's content remains welcome. See [math scope](MATH.md).
- **Quiz + Compilatio — issue #5:** on a test site with Compilatio enabled for Quiz, reproduce the original export. Include a native Essay question and, where possible, a recorded attempt with text and an attachment. Inspect Questionnaire and solutions, recorded responses, attachments and authorised teacher feedback. The archive should generate without the usage-ID-0 exception; Compilatio settings must stay unchanged. **Real Compilatio validation is still pending and issue #5 remains open.** Q01 passed internally, and automated tests use a simulated plagiarism integration. Compilatio reports/scores are not exported.
- Reply in the existing [issue #6](https://github.com/edunover/moodle-local_flatarchive/issues/6) or [issue #5](https://github.com/edunover/moodle-local_flatarchive/issues/5), specifying plugin build **2026111106**, Moodle/PHP/browser versions, the tested cases and the result. Public downloads need no GitHub account; issue replies do. Feedback can also be sent privately to [christophe@edunover.eu](mailto:christophe@edunover.eu), without sensitive course archives.

## Validation checklist

Record **Pass / Fail / Not tested**, the observed result and reproduction steps for each case. “Not tested” is not a pass.

| Case | Action | Expected result |
|---|---|---|
| Fresh installation | Install the ZIP through Moodle on an instance without Flat Archive. | Installation completes, version 0.3.0-beta.2, selection and plugin settings accessible. |
| Upgrade | On another test instance or copy, upgrade from 0.3.0-beta.1 or a beta.1+fix build; retain the older 0.2.0 upgrade case where applicable. | Courses and saved CSS preserved; upgrade completes. |
| Settings access | Use Site administration → Plugins → Local plugins → Flat archive, or the administrator link on the selection page. | Settings accessible; some local-plugin list screens do not offer this link. |
| Styles | Choose each of the three presets, then None; edit and save CSS; reopen settings. | Presets fill the field, saved edits persist, new archives use the chosen CSS. |
| Selection | Select some activities; try Select all/Deselect all. | Unsupported activities disabled; course options also selectable. |
| Course options | Export with and without Settings, Participants, Grades and Reports; also export course options only. | Pages match choices and permissions. Disabling Settings excludes course, section and activity settings. |
| Sections | Use empty, hidden and restricted sections, including a description with an image. | Accessible section settings, local media, badges and return links work. |
| Content | Test modules present in your course with files, images, links and accented characters. | Content readable within each module's README limitations. |
| Reports | Compare exports with Moodle data available to the same account. | Course/activity reports separated; anonymity, groups and hidden-grade rules respected. |
| Roles | Repeat as an editing teacher and a restricted account, not only as administrator. | Effective permissions control access; accounts without archive capability are rejected. |
| Download | Download twice within one hour, then change the confirmation language. | Same ZIP available, no regeneration or second-download error. |
| Expiry | Retry after an hour or in a different test session. | Old link cannot download outside its owning session or validity window. |
| Offline viewing | Extract the entire ZIP, disconnect and open index.html. | Pages, CSS and local media work; root contains only index.html and resources/. External links need not work offline. |
| Navigation | Visit index, activities, settings, reports and nested pages. | Internal/return links work; no unresolved double-bracket translation markers. |
| Footer | Inspect several page types. | Version, build and Beta maturity appear in the export language. |
| Volume | Gradually try larger courses on the test instance. | Record duration, ZIP size and errors; no maximum size or duration is guaranteed. |

## Known limitations

- Reading archive, not a restorable backup or interactive activity player. Grades are not recalculated.
- IMS/SCORM and `mod_hvp` excluded. Native H5P: Course Presentation, Interactive Video, Question Set, Drag and Drop, Drag the Words, with supported nested elements.
- Generation is synchronous: server memory, time and disk limits may affect large courses.
- Settings snapshots cover selected documentary fields; third-party course-format options are excluded.
- External links/media depend on their providers. Original attachments are not disinfected.
- Beta maturity does not guarantee regulatory compliance, unlimited retention or freedom from vulnerabilities. Read [SECURITY.md](../SECURITY.md).

## Submit feedback

Open the [beta feedback form](https://github.com/edunover/moodle-local_flatarchive/issues/new?template=beta_feedback.yml) and fill in the fields. A GitHub account is required. You can also find it under Issues → New issue → Beta feedback / Retour bêta. The template below remains available to prepare your feedback.

```text
Plugin version/build:
Moodle / PHP / browser:
Theme / course format / relevant plugins:
Role and specific permissions (no personal identifiers):
Test case and status (Pass / Fail / Not tested):
Reproduction steps using dummy data:
Expected result:
Observed result:
Export duration / ZIP size / approximate course volume:
Error message with personal data and secrets removed:
```

Successful feedback is useful: list the activity types actually tested. For vulnerabilities, use the private route described in SECURITY.md; do not publish exploit details in a public issue.
