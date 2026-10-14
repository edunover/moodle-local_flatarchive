# Institution appearance

Configure **Site administration → Plugins → Local plugins → Flat archive**. Only site administrators with configuration access can edit these settings.

Choose **None**, **Institutional blue**, **Modern green** or **Classic typography**. Changing the selection replaces the CSS field immediately. Edit that starting point, then save changes. Reopening the settings page preserves saved custom CSS; a preset is not reapplied automatically. None clears the override field, retaining the basic archive layout. Without JavaScript, paste CSS into the field manually.

Each new archive contains `resources/shared/institution.css`, loaded after the base stylesheet on every generated page, including nested book chapters. The saved CSS is the source of truth, regardless of the preset label. It affects neither the live Moodle site nor archives already generated. Original attached files are not restyled. Use local/system fonts and avoid remote imports or assets for offline portability. The archive's existing content security policy remains in force.

## Stable classes

Every element in the generated document body receives `flatarchive-` followed by its HTML tag: `flatarchive-body`, `flatarchive-h2`, `flatarchive-p`, `flatarchive-a`, `flatarchive-ul`, `flatarchive-li`, `flatarchive-table`, `flatarchive-th`, `flatarchive-td`, `flatarchive-img`, etc. Existing IDs and classes are preserved.

Additional semantic hooks:

| Class | Target |
| --- | --- |
| `.flatarchive-archive` | Entire generated page |
| `.flatarchive-main` | Main page content |
| `.flatarchive-title` | Main heading |
| `.flatarchive-navbar`, `.flatarchive-navbar-brand` | Course name/header |
| `.flatarchive-card`, `.flatarchive-card-body` | Content container |
| `.flatarchive-footer` | Footer |
| `.flatarchive-course-section` | Course section |
| `.flatarchive-course-tools` | Settings/Participants/Gradebook/Reports navigation |
| `.flatarchive-settings-link` | Settings action in course index |
| `.flatarchive-settings-table` | Settings/report tables |
| `.flatarchive-report-table` | Horizontally scrollable report wrapper |
| `.flatarchive-modicon` | Activity icon |
| `.flatarchive-forum-post` | Forum post |
| `.flatarchive-quiz-question` | Quiz question |
| `.flatarchive-tag` | Tag badge |

All existing `fa-*` hooks also receive a `flatarchive-*` alias at export. Legacy hooks remain for compatibility, but new institutional CSS should use `flatarchive-*`, avoiding the `fa-` convention used by Font Awesome. Existing H5P `flatarchive-*` classes are preserved.

```css
.flatarchive-archive { font-family: Arial, sans-serif; }
.flatarchive-title { color: #173f70; border-bottom: 3px solid #173f70; }
.flatarchive-course-tools > .flatarchive-a { border-radius: .4rem; }
.flatarchive-settings-table .flatarchive-th { background: #edf3fa; }
.flatarchive-a:focus-visible { outline: 3px solid #173f70; outline-offset: 3px; }
```

Keep sufficient contrast, visible keyboard focus, readable font sizes and mobile layouts. Avoid hiding archived content. Existing inline formatting and module-specific styles may need more specific selectors; these are preserved to avoid damaging original content (especially positioned H5P elements).
