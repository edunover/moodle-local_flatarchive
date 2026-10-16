# Test guide — Flat Archive 0.3.0-beta.1

[Français](BETA_TESTING.fr.md) · [README](../README.md)

Build **2026101600**, maturity **Beta**. This first campaign targets a small group with varied courses, Moodle versions and roles. The prerelease is public; invitations organise feedback rather than technically restricting downloads.

## Prepare your environment

Use a test instance, dummy accounts and synthetic or anonymised data. Back up the instance before installation or upgrade. Record exact Moodle, PHP and plugin versions, theme, course format and relevant plugins. The declared minimum is Moodle 4.4; the repository workflow defines the automated matrix.

Download `local_flatarchive-0.3.0-beta.1.zip` from the GitHub prerelease. Check its integrity against `SHA256SUMS.txt` using `shasum -a 256 local_flatarchive-0.3.0-beta.1.zip` on macOS or `sha256sum` on Linux. Compare the result to the published file. This is not a digital signature.

Archives may contain personal data. Never attach real archives, student names, grades, submissions, passwords, tokens or session keys to public issues. Do not serve archives under Moodle's authenticated origin.

## Validation checklist

Record **Pass / Fail / Not tested**, the observed result and reproduction steps for each case. “Not tested” is not a pass.

| Case | Action | Expected result |
|---|---|---|
| Fresh installation | Install the ZIP through Moodle on an instance without Flat Archive. | Installation completes, version 0.3.0-beta.1, selection and plugin settings accessible. |
| Upgrade | On another test instance or copy, upgrade from 0.2.0, preferably build 2026101500. | Courses and saved CSS preserved; upgrade completes. |
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

Open a [GitHub issue](https://github.com/edunover/moodle-local_flatarchive/issues/new). Use the beta feedback form if offered. GitHub forms depend on the default branch; until the preparation branch is integrated there, copy the template below into a regular issue.

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
