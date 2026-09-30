# local_flatarchive — CLAUDE.md

Plugin Moodle local pour archiver des ressources "Page" et "Livre" d'un cours en HTML statique + ZIP téléchargeable.
Version : 0.2.0 (2026093001) MATURITY_ALPHA. Requiert Moodle 4.4+.

---

## Documentation à maintenir

À chaque évolution significative du projet, mettre à jour **les deux fichiers** :
- `README.md` — documentation publique (fonctionnalités, installation, usage, limitations)
- `CLAUDE.md` — état technique interne (fichiers, bugs connus, conventions)

---

## Règles de versionnage

- À chaque changement déployé, incrémenter le numéro de build dans `version.php` : YYYYMMDDXX+1 (ex: 2026093001 → 2026032904)
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
- `db/services.php` — déclaration du service get_tree ; les services de jobs non implémentés ne sont pas enregistrés
- `db/install.xml` + `db/upgrade.php` — table `local_flatarchive_job`
- `classes/external/get_tree.php` — API externe complète
- `ajax/get_tree.php` + `ajax/start_job.php` — anciens endpoints conservés, non utilisés par l’interface AMD
- `ajax/generate_pdf.php` — endpoint PDF via mPDF (Phase 1, un seul module)
- `archive_process.php` — traitement synchrone : génère HTML + ZIP et redirige vers téléchargement
  - MySQL keepalive (`SELECT 1`) après chaque module et avant création ZIP (LWS wait_timeout=60s)
  - Icônes modules copiées depuis `mod/{modname}/pix/monologo.svg` vers `shared/icons/`
  - Badges de visibilité (caché / stealth / section cachée)
  - Nom ZIP : `archive_{shortname}_{YYYYMMDDHHmm}.zip`
- `serve_temp.php` — résout un token de session, vérifie propriétaire et capacité du cours, sert le ZIP puis supprime uniquement ce dossier
- `locallib.php` — helpers :
  - `flatarchive_prepare_html_for_archive()` : normalise HTML (emojis, icônes, images)
  - `local_flatarchive_visibility_badges()` : badges HTML de visibilité
  - `local_flatarchive_copy_mod_icon()` : copie l'icône SVG/PNG du module (Moodle 4.x : monologo.svg)
  - `local_flatarchive_get_release()` : lit `version.php` pour obtenir la release (ex: `0.2.0 (2026093001)`)
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

## Architecture JS : AMD

- `index.php` charge `local_flatarchive/selectors` via `js_call_amd()`.
- `amd/src/selectors.js` charge Pages et Livres via `core/ajax`, affiche les erreurs avec
  `core/notification` et charge ses chaînes avec `core/str`.
- `templates/index.mustache` contient un formulaire POST vers `archive_process.php`,
  avec `id`, `sesskey` et les cases `cmid[]`. Aucun job asynchrone n’est créé par l’interface.
- `styles.css` contient le style de l’interface Moodle ; `style/moodle.css` reste réservé aux archives.
- L’ancien `js/selectors.js` a été supprimé.
- `npm ci && npm run build` produit le module AMD nommé et sa source map dans `amd/build/`.
- `npm test` vérifie source et build avec Node test + jsdom.
- `tests/external/get_tree_test.php` contient les tests PHPUnit Moodle du service d’arborescence.
  Exécution depuis Moodle, après initialisation PHPUnit :
  `vendor/bin/phpunit local/flatarchive/tests`.
- Le build 2026093001 reste à valider dans un Moodle de test avant déploiement.

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

## Points restant à traiter avant une release

1. `ajax/generate_pdf.php` appelle `$PAGE->set_context($contextmodule)` avant la définition du contexte.
2. `render.php` reste vide et orphelin.
3. Les services de jobs et tâches cron sont encore vides : ne pas les appeler depuis l’interface.
4. L’export ZIP et son téléchargement nécessitent des tests d’intégration Moodle supplémentaires.
5. Les archives jamais téléchargées ont encore besoin d’une politique de purge ; leur téléchargement exige la session de création.

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

## Optimisations 2026093001

- `classes/archive/paths.php` : noms lisibles limités à 40 caractères avec suffixe de type/ID,
  chemins des pièces jointes isolés par module/filearea/itemid ; liens HTML encodés.
- `classes/archive/temporary_archive.php` : dossiers aléatoires et registre en session ;
  contrôle propriétaire, fichier ZIP fixe et rejet des chemins/liens symboliques non conformes.
- Helper de copie déplacé dans `locallib.php` et basé sur `stored_file::copy_content_to()`.
- Chapitres parcourus par recordset fermé dans `finally` ; résultat de génération ZIP vérifié.
- Sélection dédupliquée et validée avant création du workspace ; nettoyage en cas d’exception.
- Release mise en cache pour la durée de la requête.
- Tests : 7 arborescence + 4 chemins + 6 archives temporaires + 1 fichiers/ZIP = 18 tests PHPUnit.
- Aucune exécution PHPUnit dans cet espace sans Moodle/base de test ; essai réel indispensable.


## GitHub Actions

`.github/workflows/tests.yml` lance PHPUnit sur les dernières révisions des branches Moodle 4.4 à 5.2 avec MariaDB 10.11,
ainsi que les tests JavaScript et le contrôle du build AMD. Déclenchement à chaque push,
pull request ou lancement manuel. Moodle et la base sont créés dans le runner jetable :
aucun accès à l’instance sandbox ni secret serveur n’est nécessaire.

Référence : https://moodlehq.github.io/moodle-plugin-ci/

Validation GitHub du 30 septembre 2026 : installation Moodle 4.5 réussie ; le premier passage PHPUnit a révélé le chargement de l’API externe historique et un appel de nettoyage invalide. Passage aux classes `core_external` et à `fulldelete()`. Le second passage a révélé le libellé manquant de la capacité `flatarchive:archive`, ajouté en anglais, français et néerlandais ; validation finale en cours.

Matrice CI (14 combinaisons) : Moodle 4.4 et 4.5 avec PHP 8.1/8.2/8.3 ; Moodle 5.0 et 5.1 avec PHP 8.2/8.3/8.4 ; Moodle 5.2 avec PHP 8.3/8.4. Chaque combinaison exécute les 18 tests PHPUnit ; les 6 tests JavaScript sont exécutés une fois. Cette matrice couvre les branches stables à jour, pas chaque ancienne version corrective.

Références des prérequis : https://moodledev.io/general/releases/4.4 ; https://moodledev.io/general/releases/4.5 ; https://moodledev.io/general/releases/5.0 ; https://moodledev.io/general/releases/5.1 ; https://moodledev.io/general/releases/5.2
