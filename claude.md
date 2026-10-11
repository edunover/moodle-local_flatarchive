# local_flatarchive — CLAUDE.md

Plugin Moodle local pour archiver des ressources "Page", "Livre", "Zone texte et média", "Fichier" et "Dossier" d'un cours en HTML statique + ZIP téléchargeable.
Version : 0.2.0 (2026101101) MATURITY_ALPHA. Requiert Moodle 4.4+.

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

`.github/workflows/tests.yml` lance PHPUnit sur les dernières révisions des branches Moodle 4.4 à 5.3 avec MariaDB 10.11 (11.4 pour Moodle 5.3),
ainsi que les tests JavaScript et le contrôle du build AMD. Déclenchement à chaque push,
pull request ou lancement manuel. Moodle et la base sont créés dans le runner jetable :
aucun accès à l’instance sandbox ni secret serveur n’est nécessaire.

Référence : https://moodlehq.github.io/moodle-plugin-ci/

Validation GitHub du 30 septembre 2026 : installation Moodle 4.5 réussie ; le premier passage PHPUnit a révélé le chargement de l’API externe historique et un appel de nettoyage invalide. Passage aux classes `core_external` et à `fulldelete()`. Le second passage a révélé le libellé manquant de la capacité `flatarchive:archive`, ajouté en anglais, français et néerlandais ; validation réussie sur les 14 combinaisons Moodle/PHP.

Matrice CI (16 combinaisons) : Moodle 4.4 et 4.5 avec PHP 8.1/8.2/8.3 ; Moodle 5.0 et 5.1 avec PHP 8.2/8.3/8.4 ; Moodle 5.2 et 5.3 avec PHP 8.3/8.4. Chaque combinaison exécute les 67 tests PHPUnit ; les 6 tests JavaScript sont exécutés une fois. Cette matrice couvre les branches stables à jour, pas chaque ancienne version corrective.

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
Validation CI réussie : 35 tests PHPUnit et 231 assertions sur les 14 environnements Moodle/PHP, 6 tests JavaScript et contrôle Moodle CodeSniffer. [Exécution GitHub Actions](https://github.com/edunover/moodle-local-flatarchive/actions/runs/37941805235), code `6639581`. Huit dépréciations PHPUnit de métadonnées restent signalées sur Moodle 5.x, sans échec. Build 2026100901 installé sur la sandbox et fonctionnement manuel confirmé par l’utilisateur le 9 octobre 2026.

Jeu de test manuel : `tests/fixtures/folder/` contient un DOCX, un PDF, un PNG, deux fichiers notes.txt distincts dans des sous-dossiers et un index.html original. Les empreintes SHA-256 sont dans `tests/fixtures/folder-sha256.json`. Le ZIP de préparation inclut aussi un dossier vide (Git ne conserve pas les répertoires vides).

### Validation sandbox du Dossier — 9 octobre 2026

L’archive `archive_course-monitoring_202610091624.zip`, générée avec le build 2026100901,
contient 135 fichiers et 259 références locales sans cible manquante. Le Dossier apparaît
sous « section extra » dans l’index du cours et ouvre `folder-86.html`. Les sous-dossiers,
le dossier vide et les deux fichiers notes.txt distincts sont correctement représentés.
Les sept fichiers du jeu de test, dont DOCX, PDF et PNG, sont identiques octet pour octet
aux originaux (comparaison SHA-256). Le fichier index.html fourni dans le Dossier est
conservé séparément de l’index du cours. Aucun lien externe détecté dans href/src.
L’utilisateur confirme le fonctionnement manuel sans anomalie.

### Glossaire — build 2026100902

Les glossaires sont exportés dans `glossary-{cmid}.html`, avec description et liste de mots. Chaque mot ouvre sa propre page
`glossary-{cmid}-entry-{entryid}.html` : mot en titre, définition formatée, synonymes,
catégories et pièces jointes, avec un lien de retour au glossaire.
Les entrées approuvées sont triées par concept puis identifiant selon la collation de la base.
Les doublons disposent de pages distinctes. Les entrées en attente sont exclues, avec indication
de leur nombre ; les commentaires, évaluations, profils des auteurs et liens automatiques ne
sont pas archivés. Les filtres Moodle sont désactivés ; les ressources distantes restent externes.
Les pièces jointes sont isolées dans `files/{cmid}/entry/{entryid}/` et
`files/{cmid}/attachment/{entryid}/`, et la description dans `files/{cmid}/intro/0/`.
Les entrées transférées vers un glossaire principal sont aussi conservées dans l’export du
glossaire source, en résolvant leur contexte de fichiers réel dans le même cours.

Glossaries export a word list linking to individual offline entry pages, each with the concept
as its heading, definition, aliases, categories, attachments and a link back to the glossary. Pending entries are counted but excluded. Comments, ratings and author
profiles are not exported; external resources remain external.

Cinq tests PHPUnit supplémentaires : sélection autorisée et isolation des cours, doublons,
fichiers et ZIP, formats HTML/texte, entrées exclues, glossaire source/principal, description
et glossaire vide, mauvais type de module. Les tests vérifient aussi les pages individuelles,
les titres, les liens aller/retour et leur conservation dans le ZIP.
Validation CI réussie : 40 tests PHPUnit et 285 assertions sur les 14 environnements Moodle/PHP,
6 tests JavaScript et contrôle Moodle CodeSniffer. [Exécution GitHub Actions](https://github.com/edunover/moodle-local-flatarchive/actions/runs/37945527767),
code `b91174f`. Neuf dépréciations PHPUnit de métadonnées sont signalées sur Moodle 5.x, sans échec.
Build 2026100902 non déployé sur la sandbox ; validation manuelle à réaliser.

### Auteurs et commentaires du glossaire — build 2026100903

Chaque définition affiche le nom de son auteur. Les commentaires accessibles sont conservés
dans l’ordre chronologique avec leur auteur et leur date. Les réglages de commentaires du
site et du glossaire, ainsi que les capacités Moodle de lecture et de commentaire, sont respectés.
Les commentaires restent isolés par contexte, composant et entrée, y compris pour les glossaires
source/principal. Les comptes supprimés ou absents apparaissent comme auteur inconnu ou supprimé.
Aucun profil, adresse électronique ou avatar n’est ajouté. Les évaluations restent exclues.
Les filtres dynamiques des commentaires sont désactivés ; les liens externes restent externes.

Le build 2026100902 est installé et son fonctionnement confirmé par l’utilisateur.
Le ZIP du 9 octobre à 17:02 est validé : 141 fichiers, 276 références locales sans cible manquante,
une liste et trois pages de définition. Build 2026100903 : 42 tests PHPUnit et 300 assertions réussis sur les 14 configurations,
6 tests JavaScript et contrôle Moodle CodeSniffer réussis. [Exécution GitHub Actions](https://github.com/edunover/moodle-local-flatarchive/actions/runs/37950134719),
code `ca50a4c`. Validation manuelle à réaliser après installation.

### Wiki — build 2026101001

Export de la dernière révision des pages Wiki (HTML, Creole et NWiki), avec index par espace,
liens internes hors ligne, fichiers partagés par sous-wiki, auteur de la dernière révision et
commentaires accessibles datés. Les espaces individuels et par groupe suivent les règles
`wiki_user_can_view()` de Moodle ; seuls leurs fichiers sont copiés. Les pages non archivées
sont signalées et leurs liens désactivés. Les ressources externes restent externes.
L’historique des révisions, les opérations d’édition et le multilinguisme ne sont pas inclus.
L’export ne modifie ni les pages, ni leur cache, ni leurs compteurs de consultation.

Chaque wiki a un index `wiki-{cmid}.html` et des pages `wiki-{cmid}-page-{pageid}.html`.
Les fichiers sont conservés dans `files/{cmid}/attachments/{subwikiid}/`.
Six tests PHPUnit supplémentaires couvrent sélection, révisions, navigation, fichiers/ZIP,
formats, commentaires, espaces individuels et groupes séparés, droits et wiki vide.
Validation CI réussie : 48 tests PHPUnit et 365 assertions sur les 14 configurations,
6 tests JavaScript et contrôle Moodle CodeSniffer réussis. [Exécution GitHub Actions](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38057074490),
code `fe091da`. Dix dépréciations PHPUnit de métadonnées sont signalées sur Moodle 5.x, sans échec.
Build 2026101001 non installé sur la sandbox ; test manuel à réaliser.

Le glossaire 2026100903 est installé et validé : trois auteurs et trois commentaires datés
dans l’archive du 9 octobre à 17:23 ; 276 références locales sans cible manquante.
Le multilinguisme reste prévu après les activités : `{mlang}`, balises FilterCodes
`{multilang xx}…{/multilang}` et HTML multilang, avec navigation entre traductions sur chaque page.

### Taille du ZIP — build 2026101002

La page de téléchargement affiche la taille réelle du ZIP terminé à côté du message de
confirmation, dans le titre et la notification. Le formatage utilise `display_size()` de
Moodle et les messages sont traduits en français, anglais et néerlandais. Si la taille
ne peut pas être lue, le message de confirmation reste affiché sans taille.

The download page shows the completed ZIP size next to the success message, using Moodle’s
localized size formatting. Manual verification on the sandbox remains pending.

### Validation du 10 octobre 2026

L’utilisateur confirme le fonctionnement du Wiki sur la sandbox. Le ZIP
`archive_course-monitoring_202610101627.zip` est intact : 147 fichiers, 300 références
locales sans cible manquante. L’index Wiki et ses trois pages, l’image, le fichier joint,
les auteurs et le commentaire sont présents. L’accueil contient la version finale, sans
la version initiale ; aucun lien de création/édition Moodle ni marqueur de fichier ne
subsiste dans les pages Wiki.

Le build 2026101002 (taille du ZIP sur la page de téléchargement) passe la CI :
https://github.com/edunover/moodle-local-flatarchive/actions/runs/38058888279
La vérification visuelle de cet affichage sur la sandbox reste à réaliser.

### Icônes de sélection — build 2026101003

La sélection affiche l’icône Moodle de chaque activité entre la case à cocher et son titre.
L’URL provient de `cm_info::get_icon_url()` ; le nom localisé du type sert de texte alternatif.
Les icônes conservent leurs proportions dans un espace de 24 pixels, y compris pour les titres longs.
Le rendu utilise des éléments DOM, sans interpréter le titre comme du HTML.

The selection page displays Moodle activity icons before resource titles, with a localized
activity-type alternative text. The existing selection and submission tests cover this display.

Validation du build 2026101003 : les 14 configurations Moodle/PHP, les 6 tests JavaScript
et le contrôle Moodle CodeSniffer passent. Vérification visuelle sur la sandbox à réaliser.
https://github.com/edunover/moodle-local-flatarchive/actions/runs/38060730033

### Feedback — build 2026101004

Export statique du questionnaire, avec choix multiples et pondérés, questions numériques et
textuelles, informations, étiquettes illustrées, sauts de page, caractère obligatoire et
conditions. Une page par réponse soumise accessible via `mod/feedback:viewreports` et les
règles Moodle de visibilité des groupes. Sans ce droit, seul le questionnaire est exporté.
Les réponses nominatives conservent nom et date ; chaque réponse anonyme conserve son statut
initial, sans nom, identifiant utilisateur, date ni information automatique. Leur ordre est
mélangé dans l’archive sans modifier Moodle. Le texte libre est conservé tel que saisi et peut
contenir des informations fournies par le répondant : il ne fait pas l’objet d’une anonymisation
sémantique. Les comptes supprimés sont exclus des réponses nominatives comme dans les rapports Moodle.

Index `feedback-{cmid}.html`, pages `feedback-{cmid}-response-{number}.html`, médias isolés
par module et élément. Les brouillons, statistiques agrégées, formulaires interactifs,
redirections après soumission et réponses de feedbacks de site rattachées à d’autres cours
ne sont pas inclus. Les types de questions tiers sont signalés sans interpréter leur configuration.
Les valeurs absentes (question sautée ou sans réponse enregistrée) ne sont pas inventées.

Feedback exports include the static questionnaire and individual submitted responses permitted
by report capabilities and Moodle group visibility. Anonymous submissions omit identity and
time metadata; free text remains unchanged. No interactive forms or aggregate reports are exported.
Six additional PHPUnit tests cover selection, ZIP/media, question formats, anonymity, response
isolation, capabilities, separate groups and empty/unsupported questionnaires.

### Matrice Moodle 5.3

La suite complète couvre désormais 16 configurations : ajout de MOODLE_503_STABLE avec
PHP 8.3 et 8.4, sur MariaDB 11.4 (minimum requis par Moodle 5.3). Les 14 configurations
Moodle 4.4 à 5.2 conservent MariaDB 10.11. Le contrôle des standards PHP est également
exécuté sur Moodle 5.3 / PHP 8.4. Les tests JavaScript restent communs aux versions.
Référence : https://moodledev.io/general/releases/5.3

Validation Feedback avant extension : les 14 configurations Moodle/PHP et le job JavaScript ont réussi :
https://github.com/edunover/moodle-local-flatarchive/actions/runs/38080207773 (commit `47abe8f`).
La validation Moodle 5.3 est en attente de la prochaine exécution.

### Icônes dans l’index hors connexion — build 2026101005

Chaque activité exportée (Page, Livre, Texte et média, Fichier, Dossier, Glossaire,
Wiki et Feedback) reçoit désormais son icône officielle Moodle dans l’index du ZIP.
Les images sont stockées une seule fois par type dans `shared/icons/` ; aucun accès
au site Moodle n’est nécessaire pour les afficher. Le nom du type sert de texte alternatif.
La présentation des zones Texte et média et les liens existants sont conservés.

Feedback : fonctionnement manuel confirmé par l’utilisateur le 10 octobre 2026.
La matrice complète Moodle 4.4 à 5.3 a réussi avant cette modification :
https://github.com/edunover/moodle-local-flatarchive/actions/runs/38080563571.

### Consultation (Choice) — build 2026101006

Export statique de la description et de ses médias, des options dans leur ordre,
du mode unique/multiple et des limites de réponse configurées. Une page
`choice-{cmid}.html` est liée depuis l’index, avec l’icône officielle hors connexion.
Les résultats publiés suivent `choice_can_view_results` (jamais, après réponse,
après fermeture ou toujours). Les effectifs anonymes ne contiennent aucun nom.
Les résultats nominatifs publiés peuvent inclure les noms ; le rapport privé exige
`mod/choice:readresponses` et `mod/choice:downloadresponses`.
Le groupe courant, les inscriptions actives/inactives et la visibilité des groupes
sont respectés via les API Moodle. Les réponses multiples sont comptées par option.
Les non-répondants, dates individuelles et formulaires interactifs sont exclus.
Aucune réponse ni donnée de complétion n’est modifiée lors de l’export.

Tests ajoutés : médias, réponses multiples, publication, anonymat, droits de rapport,
groupes séparés et sélection limitée au cours ; icône Choice ajoutée au test ZIP.
Validation Choice terminée : 61 tests PHPUnit et 539 assertions, six tests JavaScript,
et contrôles de standards réussis sur les 16 configurations Moodle/PHP jusqu’à Moodle 5.3 :
https://github.com/edunover/moodle-local-flatarchive/actions/runs/38083156703 (code `f0d9f59`).
Build 2026101006 installé et test manuel confirmé par l’utilisateur le 10 octobre 2026.
Archive `archive_Critical thinking_202610102226.zip` vérifiée : ZIP intact,
page `choice-111.html` liée depuis l’index, icône locale présente, 168 références locales
sans cible manquante, aucun formulaire actif ni marqueur de fichier Moodle non résolu.
Le multilinguisme reste différé à la fin de l’intégration des activités.

### Forum — build 2026101101

Une page liste les discussions accessibles, puis une page par discussion présente les
messages chronologiquement avec liens vers les messages parents visibles, auteurs,
dates de création et modification, médias incorporés et pièces jointes. L’index du
cours inclut l’icône officielle. Les fichiers sont isolés par module, zone et message.

La sélection de contenu utilise `forum_user_can_see_discussion` et
`forum_user_can_see_post` avant toute copie de fichiers : droits de lecture, disponibilité
du module, groupes séparés, discussions programmées, réponses privées et délai des
forums Questions-réponses sont respectés. Les messages supprimés sont exclus ; l’auteur
du message initial d’un forum à discussion unique reste masqué comme dans Moodle.
Les auteurs supprimés sont remplacés par un libellé générique. Les profils ne sont pas exportés.

Aucun marquage comme lu, abonnement, notification ou changement de complétion.
Pas de formulaires, évaluations, notes, historique de modification ni liste d’abonnés.
Les liens externes ou liens Moodle présents dans le texte restent des liens vers leur
cible d’origine ; les médias locaux et la navigation générée sont autonomes.
Le multilinguisme reste différé. Tests ajoutés pour fichiers, navigation, absence de
marquage, groupes, dates, suppression, réponses privées, Q/R, auteurs masqués et droits.
La validation manuelle du Forum reste à effectuer.
