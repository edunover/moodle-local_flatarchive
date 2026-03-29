# local_flatarchive — CLAUDE.md

Plugin Moodle local pour archiver des ressources "Page" d'un cours en PDF regroupés dans un ZIP.
Version : 0.1.0 MATURITY_ALPHA. Requiert Moodle 4.4+.

---

## État d'avancement

Le plugin est à ~30% de son objectif. La couche sécurité/navigation/API est en place,
le cœur fonctionnel (archivage, PDF, ZIP, téléchargement) reste à implémenter.

### Implémenté et fonctionnel
- `index.php` — page d'entrée avec auth + capability
- `lib.php` — hook navigation cours
- `db/access.php` — capability `local/flatarchive:archive`
- `db/services.php` — déclaration des 3 web services
- `classes/external/get_tree.php` — API externe complète
- `templates/index.mustache` — template UI
- `lang/fr|en|nl/` — i18n 3 langues
- `version.php`, `vendor/mpdf/` (autoloader)

### Fichiers vides à implémenter
- `classes/external/start_job.php` — démarrer un job
- `classes/external/get_status.php` — état d'un job
- `classes/task/process_job.php` — tâche cron de traitement
- `classes/task/purge_temp.php` — tâche cron de nettoyage
- `classes/pdf/mpdf_engine.php` — génération PDF via mPDF
- `classes/output/renderer.php` — renderer Moodle
- `db/tasks.php` — déclaration des tâches cron
- `pluginfile.php` — servir les fichiers archivés
- `render.php` — à clarifier (probablement à supprimer)

---

## Architecture JS : migration AMD en cours

Il existe deux fichiers JS qui font la même chose. **Seul l'AMD doit subsister à terme.**

| Fichier | Statut | Chargé par |
|---|---|---|
| `js/selectors.js` | Non-AMD, DEBUG, à supprimer | `index.php` (actuellement) |
| `amd/src/selectors.js` | AMD standard Moodle, à finaliser | Personne (orphelin) |

### Actions requises
1. Finaliser `amd/src/selectors.js` et le brancher dans `index.php` via `$PAGE->requires->js_call_amd()`
2. Supprimer `js/selectors.js` et le dossier `js/`
3. Supprimer le bloc `// DEBUG ONLY` dans `index.php` et restaurer le require AMD propre

---

## Conventions à respecter

### PHP / Moodle
- Toujours `defined('MOODLE_INTERNAL') || die();` en tête de chaque fichier inclus
- Sécurité systématique : `require_login()` → `validate_context()` → `require_capability()`
- Web services : pattern strict `execute_parameters()` / `execute()` / `execute_returns()`
- `get_course()` lève une exception si introuvable — ne pas tester `if (!$course)`
- Utiliser `get_fast_modinfo()` pour accéder à la structure du cours

### JavaScript (AMD uniquement)
- Utiliser `core/ajax`, `core/notification`, `core/str` — pas de `fetch` brut ni de `alert()`
- Toutes les chaînes affichées via `Str.get_string('key', 'local_flatarchive')` — zéro string hardcodée
- Sélecteurs dans un objet `selectors` en haut du module
- Pattern : `return { init }` avec `init(params)` appelé depuis `js_call_amd()`

### Templates Mustache
- Pas de bloc `<style>` inline — utiliser un fichier CSS séparé
- Attributs `data-action` et `data-region` doivent correspondre exactement aux sélecteurs JS

### i18n
- Toute chaîne visible par l'utilisateur doit être dans `lang/en/local_flatarchive.php` (référence)
- Dupliquer dans `lang/fr/` et `lang/nl/`

---

## Bugs connus à corriger avant la prochaine release

1. **Sélecteur cassé dans l'AMD** — `archive: '[data-action="archive"]'` doit être `'[data-action="start-archive"]'`
2. **`[data-region="hint"]` absent du template** — le supprimer de `amd/src/selectors.js` ou l'ajouter au template
3. **`render.php` et `pluginfile.php` vides** — implémenter ou supprimer selon besoin
4. **CSS inline dans `index.mustache`** — extraire dans `styles.css`

---

## Dépendances

- **mPDF** : embarqué dans `vendor/mpdf/`, chargé via `vendor/autoload.php`
  - À terme : migrer vers Composer + `thirdpartylibs.xml` selon les standards Moodle
  - Pour l'instant : vérifier que `vendor/autoload.php` est bien appelé avant tout usage de `\Mpdf\Mpdf`

---

## Ce qu'il ne faut pas faire

- Ne pas ajouter de nouveaux fichiers non-AMD dans `js/`
- Ne pas utiliser `alert()` ou `confirm()` natifs
- Ne pas hardcoder de texte français dans le PHP ou le JS
- Ne pas bypasser la vérification de capability dans les web services
- Ne pas commiter de code de debug (`console.log`, `// DEBUG ONLY`, cache-buster `time()`)
  
  ## Ressources de référence
  - 'docs/moodle 4.0 e-learning.md'
  - 'docs/Brand guidelines.md
  - 'docs/h5p.md'
  - 'docs/MAQ.md'
  - 'docs/moodle DB.md'
  - 'docs/Moodle Dispensez vos formations en ligne.md'
  - 'docs/moodle security.md'
  - 'docs/moodle_4_0_e_learning.md'
  - 'docs/moodle.DB.relationships.implied.large.png'  