# local_flatarchive — CLAUDE.md

Plugin Moodle local pour archiver des ressources "Page", "Livre", "Zone texte et média", "Fichier" et "Dossier" d'un cours en HTML statique + ZIP téléchargeable.
Version : 0.2.0 (2026100901) MATURITY_ALPHA. Requiert Moodle 4.4+.

---

## Documentation à maintenir

À chaque évolution significative du projet, mettre à jour **les deux fichiers** :
- `README.md` — documentation publique (fonctionnalités, installation, usage, limitations)
- `CLAUDE.md` — état technique interne (fichiers, bugs connus, conventions)

---

## Règles de versionnage

- À chaque changement déployé, incrémenter le numéro de build dans `version.php` : YYYYMMDDXX+1 (ex: 2026093001 → 2026093002)
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
- `serve_temp.php` — résout un token de session, vérifie propriétaire et capacité du cours, sert le ZIP plusieurs fois pendant une heure ; suppression différée par le nettoyage temporaire Moodle
- `locallib.php` — helpers :
  - `flatarchive_prepare_html_for_archive()` : normalise HTML (emojis, icônes, images)
  - `local_flatarchive_visibility_badges()` : badges HTML de visibilité
  - `local_flatarchive_copy_mod_icon()` : copie l'icône SVG/PNG du module (Moodle 4.x : monologo.svg)
  - `local_flatarchive_get_release()` : lit `version.php` pour obtenir la release (ex: `0.2.0 (2026100101)`)
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
- Le build 2026093001 est installé sur la sandbox Moodle 4.5 ; la génération des 50 Pages du cours TEST- réussit. Le téléchargement manuel et les 203 références locales du ZIP sont validés ; l’affichage visuel hors ligne reste à vérifier.

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
5. Les liens de téléchargement expirent après une heure et exigent la session de création ; la suppression physique dépend du nettoyage temporaire planifié de Moodle.

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
- Tests : 8 arborescence + 4 chemins + 6 archives temporaires + 1 fichiers/ZIP + 2 structure de l’index + 3 zones texte et média = 24 tests PHPUnit.
- Les 18 tests PHPUnit passent dans GitHub Actions sur les 14 combinaisons Moodle/PHP documentées ci-dessous.


## GitHub Actions

`.github/workflows/tests.yml` lance PHPUnit sur les dernières révisions des branches Moodle 4.4 à 5.2 avec MariaDB 10.11,
ainsi que les tests JavaScript et le contrôle du build AMD. Déclenchement à chaque push,
pull request ou lancement manuel. Moodle et la base sont créés dans le runner jetable :
aucun accès à l’instance sandbox ni secret serveur n’est nécessaire.

Référence : https://moodlehq.github.io/moodle-plugin-ci/

Validation GitHub du 30 septembre 2026 : installation Moodle 4.5 réussie ; le premier passage PHPUnit a révélé le chargement de l’API externe historique et un appel de nettoyage invalide. Passage aux classes `core_external` et à `fulldelete()`. Le second passage a révélé le libellé manquant de la capacité `flatarchive:archive`, ajouté en anglais, français et néerlandais ; validation réussie sur les 14 combinaisons Moodle/PHP.

Matrice CI (14 combinaisons) : Moodle 4.4 et 4.5 avec PHP 8.1/8.2/8.3 ; Moodle 5.0 et 5.1 avec PHP 8.2/8.3/8.4 ; Moodle 5.2 avec PHP 8.3/8.4. Chaque combinaison exécute les 35 tests PHPUnit ; les 6 tests JavaScript sont exécutés une fois. Cette matrice couvre les branches stables à jour, pas chaque ancienne version corrective.

Références des prérequis : https://moodledev.io/general/releases/4.4 ; https://moodledev.io/general/releases/4.5 ; https://moodledev.io/general/releases/5.0 ; https://moodledev.io/general/releases/5.1 ; https://moodledev.io/general/releases/5.2

## Validation du 1er octobre 2026

- GitHub Actions : 14 environnements PHPUnit et le job JavaScript réussis : https://github.com/edunover/moodle-local-flatarchive/actions/runs/36734923014 (commit `39dbae6`).
- Sandbox Moodle 4.5.11+, PHP 8.3.33 : installation du build 2026093001 réussie ; accès par le menu du cours ; sélection et génération des 50 Pages du cours TEST- réussies, sans modifier le contenu du cours.
- Téléchargement manuel réussi, confirmé par le ZIP fourni : intégrité correcte, 54 fichiers dont 50 Pages, index.html, about.html, CSS et icône. Les 203 références locales sont valides ; aucun lien externe détecté dans les attributs href/src. La récupération automatisée du navigateur intégré échoue ; les reprises de téléchargement restent à examiner, car le ZIP est supprimé après envoi.
- Aucun essai navigateur des Livres n’est encore confirmé. L’aperçu visuel local est bloqué par la politique du navigateur intégré (file:// non autorisé) ; la structure et les liens de l’archive ont été contrôlés directement.

## Prochaine livraison — libellé de retour

`archive_process.php` utilise `backtoflatarchive` pour le lien de retour après génération.
Chaîne ajoutée en anglais, français et néerlandais. Le nom du plugin reste inchangé.
Modification non déployée : incrémenter le build à la prochaine livraison.

### Contrôle supplémentaire : Livre PPT2Book

Le ZIP fourni `archive_test-booking_202610011800.zip` correspond au cours « test booking », Livre « PPT2Book » (module 66), et non au Livre « test ppt » de 60 chapitres préparé dans « Course monitoring ».
Contrôle réussi : ZIP intact, 4 chapitres, 6 images PNG valides et distinctes (7 références), 26 références locales sans cible manquante, aucune URL externe dans href/src ni marqueur @@PLUGINFILE@@ résiduel. Les trois fichiers nommés slide.png sont conservés dans des répertoires de chapitres distincts. Le test des 60 chapitres avec titres répétés reste à effectuer sur le ZIP correspondant.

### Prochaine version : structure de l’index HTML

L’index regroupe les ressources exportées sous les noms des sections Moodle, dans l’ordre du cours (par exemple « New section » puis « test ppt »). Seules les sections contenant une ressource sélectionnée sont incluses ; des sections de même nom restent distinctes. Les noms de sections par défaut suivent la langue de Moodle. Les liens des Pages et des Livres, ainsi que leurs badges de visibilité, sont conservés. Modification préparée, non déployée.

### Validation du build 2026100101

Les 20 tests PHPUnit passent sur les 14 combinaisons Moodle/PHP, ainsi que les 6 tests JavaScript : https://github.com/edunover/moodle-local-flatarchive/actions/runs/36889554729 (code `1575049`). Le build 2026100101 regroupe l’index par sections et le lien de retour traduit ; le numéro de build et cette documentation ont été mis à jour après ces tests. Paquet préparé pour installation ; la sandbox reste au build 2026093001.

### Zones texte et média — build 2026100102

Les zones texte et média (`mod_label`) sont sélectionnables et affichées directement dans
`index.html`, à leur position dans la section du cours. Le contenu conserve son format Moodle
et les badges de visibilité. Les pièces jointes du champ introduction sont copiées dans
`files/{cmid}/intro/0/`, avec leurs sous-dossiers ; les liens deviennent relatifs.
Deux zones peuvent donc utiliser le même nom de fichier sans écrasement.
Les fichiers distants ne sont pas téléchargés. Les filtres Moodle sont désactivés pour éviter
une dépendance à leurs scripts ou services ; les intégrations externes restent dépendantes du réseau.

Text and media areas are rendered inline in the course index. Introduction attachments are
isolated per course module and linked locally; remote media and Moodle filters are not captured.

Quatre tests PHPUnit supplémentaires couvrent la sélection par un enseignant, l’isolation entre
cours, le contenu inline et son ordre, les fichiers de même nom et les chemins avec espaces dans
le ZIP, le format texte brut et le rejet d’un autre type de module. Le test JavaScript de soumission
inclut maintenant une zone texte et média. Le contrôle Moodle CodeSniffer des fichiers PHP touchés par cette évolution est ajouté à GitHub Actions ; il ne certifie pas encore tout le code historique.
Validation réussie : 24 tests PHPUnit sur les 14 combinaisons Moodle/PHP, 6 tests JavaScript et contrôle Moodle CodeSniffer des 9 fichiers PHP concernés. Exécution : https://github.com/edunover/moodle-local-flatarchive/actions/runs/36892945413 (code `c045465`). Non déployé sur la sandbox.

### Validation sandbox du build 2026100102

Installation réussie. Le ZIP `archive_course-monitoring_202610021934.zip` contient 126 fichiers,
60 chapitres et la zone texte et média avec son PNG embarqué (1054 × 1032).
Les 248 références locales sont valides ; aucun lien externe ni marqueur Moodle résiduel détecté.
Le rendu visuel hors ligne est confirmé par l’utilisateur le 2 octobre 2026.

### Ressource Fichier — build 2026100201

Les ressources Fichier (`mod_resource`) sont sélectionnables. Le lien dans la section de l’index
ouvre le fichier principal, choisi selon l’ordre Moodle (`sortorder DESC, id ASC`).
Tous les fichiers associés sont conservés, octet pour octet, dans `files/{cmid}/content/0/`,
avec leurs sous-dossiers et noms d’origine. Les espaces et caractères spéciaux sont encodés
uniquement dans les liens. Un fichier manquant bloque explicitement la génération.
Les documents nécessitent toujours un lecteur adapté ; aucune conversion PDF/HTML n’est effectuée.
La description de la ressource n’est pas exportée à ce stade. Les dépendances externes d’un
fichier HTML restent externes ; seuls ses fichiers stockés dans Moodle sont embarqués.

File resources link to their original main file from the course index. Companion files and
subdirectories are preserved, isolated by module ID. No document conversion is performed.
Four additional PHPUnit tests cover selection, main-file order, companion files, duplicate
filenames, URL encoding, ZIP contents, missing files and wrong module types.
Validation CI réussie : 28 tests PHPUnit sur 14 environnements Moodle/PHP, 6 tests JavaScript et contrôle Moodle CodeSniffer. Exécution : https://github.com/edunover/moodle-local-flatarchive/actions/runs/37042345425 (code `eb8fea2`). Build 2026100201 installé sur la sandbox. Archive réelle vérifiée le 3 octobre 2026 : 127 fichiers, liens locaux valides, ressource TXT avec espaces et accents conservée octet pour octet ; section « New section » contenant le livre, la zone texte et média et le fichier.

### Téléchargements répétés — build 2026100301

Le premier téléchargement ne supprime plus le ZIP. Le même lien peut être utilisé pendant
une heure après génération, par son propriétaire dans la même session et avec la capacité
requise sur le cours. La durée est fixe et ne se prolonge pas à chaque téléchargement.
Un ZIP expiré ou absent affiche une page informative traduite (en/fr/nl), avec accès aux cours,
au lieu d’une exception Moodle. Il faut alors générer une nouvelle archive.
Les fichiers temporaires restent gérés par le nettoyage planifié de Moodle ; l’expiration du
lien au bout d’une heure ne signifie pas une suppression physique immédiate.

Repeated downloads are supported for one hour in the originating session. Expired or missing
archives show a friendly page. Moodle cron handles eventual temporary-file cleanup.
Trois tests PHPUnit supplémentaires couvrent les lectures répétées, l’expiration et le fichier disparu.
Validation CI réussie : 31 tests PHPUnit et 150 assertions sur les 14 environnements Moodle/PHP, 6 tests JavaScript et contrôle Moodle CodeSniffer. [Exécution GitHub Actions](https://github.com/edunover/moodle-local-flatarchive/actions/runs/37124447456), code `c76fbe3`. PHPUnit signale encore 7 dépréciations de métadonnées sur Moodle 5.x, sans échec. Build 2026100301 installé sur la sandbox le 9 octobre 2026. Deux téléchargements successifs du même ZIP sans erreur confirmés par l’utilisateur.

### Ressource Dossier — build 2026100901

Les Dossiers (`mod_folder`) sont sélectionnables dans leur section. Chaque dossier a une page
`folder-{cmid}.html`, avec sa description, ses médias embarqués, ses badges de visibilité et une
arborescence de liens locaux. Les fichiers originaux sont copiés dans `files/{cmid}/content/0/`,
les pièces jointes de description dans `files/{cmid}/intro/0/`. Les noms, sous-dossiers et octets
sont conservés. Les dossiers vides sont représentés dans l’arborescence HTML, sans entrée de
répertoire vide garantie dans le ZIP. Les fichiers sont triés naturellement, dossiers en premier.
Le rendu utilise une page dédiée même si Moodle affiche le dossier directement dans le cours.
Aucune conversion de documents ni capture des dépendances externes ; filtres de description désactivés.

Folder resources export to a dedicated offline HTML page with a description and nested file tree.
Original files and paths are preserved; empty folders appear in the HTML tree. Documents require
an appropriate reader. Remote dependencies remain remote.

Quatre tests PHPUnit supplémentaires couvrent la sélection par un enseignant, l’isolation entre
cours, les sous-dossiers imbriqués et vides, les noms avec accents et caractères spéciaux, les
homonymes, le fichier original index.html, les médias de description, les badges, les liens et
la conservation des octets après compression/décompression. Le test JavaScript inclut un Dossier.
Validation CI réussie : 35 tests PHPUnit et 231 assertions sur les 14 environnements Moodle/PHP, 6 tests JavaScript et contrôle Moodle CodeSniffer. [Exécution GitHub Actions](https://github.com/edunover/moodle-local-flatarchive/actions/runs/37941805235), code `6639581`. Huit dépréciations PHPUnit de métadonnées restent signalées sur Moodle 5.x, sans échec. Build non installé ; la sandbox utilise 2026100301, avec double téléchargement validé.

Jeu de test manuel : `tests/fixtures/folder/` contient un DOCX, un PDF, un PNG, deux fichiers notes.txt distincts dans des sous-dossiers et un index.html original. Les empreintes SHA-256 sont dans `tests/fixtures/folder-sha256.json`. Le ZIP de préparation inclut aussi un dossier vide (Git ne conserve pas les répertoires vides).
