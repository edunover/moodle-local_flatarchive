# local_flatarchive — CLAUDE.md

Plugin Moodle local pour archiver des ressources "Page" et "Livre" d'un cours en HTML statique + ZIP téléchargeable.
Version : 0.2.0 (2026032903) MATURITY_ALPHA. Requiert Moodle 4.4+.

---

## Documentation à maintenir

À chaque évolution significative du projet, mettre à jour **les deux fichiers** :
- `README.md` — documentation publique (fonctionnalités, installation, usage, limitations)
- `CLAUDE.md` — état technique interne (fichiers, bugs connus, conventions)

---

## Règles de versionnage

- À chaque changement déployé, incrémenter le numéro de build dans `version.php` : YYYYMMDDXX+1 (ex: 2026032903 → 2026032904)
- Si le jour change, repartir à 01 (ex: 2026033001)
- Incrémenter `$plugin->release` (mineur ou patch) uniquement sur demande explicite

---

## État d'avancement

Le plugin est à ~70% de son objectif. L'archivage HTML (Pages + Livres) est fonctionnel de bout en bout.
Reste à implémenter : la couche cron/jobs asynchrones et la génération PDF.

### Implémenté et fonctionnel
- `index.php` — page d'entrée avec auth + capability
- `lib.php` — hook navigation cours
- `db/access.php` — capability `local/flatarchive:archive`
- `db/services.php` — déclaration des 3 web services
- `db/install.xml` + `db/upgrade.php` — table `local_flatarchive_job`
- `classes/external/get_tree.php` — API externe complète
- `ajax/get_tree.php` + `ajax/start_job.php` — endpoints AJAX (non-AMD, actifs)
- `ajax/generate_pdf.php` — endpoint PDF via mPDF (Phase 1, un seul module)
- `archive_process.php` — traitement synchrone : génère HTML + ZIP et redirige vers téléchargement
  - MySQL keepalive (`SELECT 1`) après chaque module et avant création ZIP (LWS wait_timeout=60s)
  - Icônes modules copiées depuis `mod/{modname}/pix/monologo.svg` vers `shared/icons/`
  - Badges de visibilité (caché / stealth / section cachée)
  - Nom ZIP : `archive_{shortname}_{YYYYMMDDHHmm}.zip`
- `serve_temp.php` — sert le ZIP depuis tempdir puis supprime le dossier
- `locallib.php` — helpers :
  - `flatarchive_prepare_html_for_archive()` : normalise HTML (emojis, icônes, images)
  - `local_flatarchive_visibility_badges()` : badges HTML de visibilité
  - `local_flatarchive_copy_mod_icon()` : copie l'icône SVG/PNG du module (Moodle 4.x : monologo.svg)
  - `local_flatarchive_get_release()` : lit `version.php` pour obtenir la release (ex: `0.2.0 (2026032903)`)
- `archive_templates/` — 4 templates HTML autonomes : index, about, page, book_toc
  - Footer : `Flatarchive {release} © EDUNOVER – {coursetitle}`
  - Badges de visibilité sur page individuelle et TOC livre
- `style/moodle.css` — CSS minimal pour les archives HTML générées (badges, icônes, layout)
- `templates/index.mustache` — template UI (bouton "Générer l'archive HTML" uniquement)
- `lang/fr|en|nl/` — i18n 3 langues
- `version.php`, `composer.json`, `composer.lock` (mpdf v8.2.7)

### Fichiers vides (à implémenter ou supprimer)
- `classes/external/start_job.php` — version web service de start_job (doublon de ajax/start_job.php)
- `classes/external/get_status.php` — état d'un job
- `classes/task/process_job.php` — tâche cron de traitement asynchrone
- `classes/task/purge_temp.php` — tâche cron de nettoyage
- `classes/pdf/mpdf_engine.php` — moteur PDF réutilisable (remplacera ajax/generate_pdf.php)
- `classes/output/renderer.php` — renderer Moodle
- `db/tasks.php` — déclaration des tâches cron
- `pluginfile.php` — servir les fichiers archivés via l'API fichiers Moodle
- `render.php` — orphelin, à supprimer

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
3. **CSS inline dans `index.mustache`** — `style/moodle.css` existe désormais, extraire les styles dans ce fichier
4. **`ajax/generate_pdf.php` ligne 18** — `$PAGE->set_context($contextmodule)` appelé avant que `$contextmodule` soit défini
5. **`render.php` vide** — orphelin, à supprimer
6. **`js/selectors.js`** — contient `console.log` debug et cache-buster `time()` — à remplacer par AMD

---

## Dépendances

- **mPDF v8.2.7** : géré via Composer (`composer.json` + `composer.lock`)
- `vendor/` est dans `.gitignore` — recréer avec `composer install` sur un nouveau serveur
- Appel systématique : `require_once(__DIR__ . '/vendor/autoload.php');` avant tout usage de `\Mpdf\Mpdf`

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