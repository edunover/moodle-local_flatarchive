# local_flatarchive

> [Français](#français) | [English](#english)

---

## Français

Plugin local Moodle — Archive les ressources d'un cours (Pages, Livres, Zones texte et média, Fichiers, Dossiers, Glossaires, Wikis, Feedbacks, Consultations, Forums et Leçons) en pages HTML statiques autonomes regroupées dans un ZIP téléchargeable.

**Version :** 0.2.0 (2026101107) · MATURITY_ALPHA · Requiert Moodle 4.4+

### Fonctionnalités

Flatarchive permet aux enseignants d'exporter des ressources de cours vers une archive portable utilisable hors ligne :

- Sélection des activités **Page**, **Livre**, **Zone texte et média**, **Fichier**, **Dossier**, **Glossaire**, **Wiki**, **Feedback** et **Consultation**, **Forum** et **Leçon** depuis l'arborescence du cours
- Génération d'un **site HTML statique** — aucune dépendance Moodle après téléchargement
- Réécriture des liens `@@PLUGINFILE@@` en chemins relatifs et copie des fichiers embarqués
- **Badges de visibilité** (caché / caché de la page de cours / section cachée) sur chaque page et dans l'index
- **Icônes de module** (Moodle 4.x `monologo.svg`) dans l'index pour identification rapide
- Archive nommée `archive_{shortname}_{JJMMAAAAHHmm}.zip`
- Pied de page sur chaque page : `Flatarchive {version} © EDUNOVER`

### Structure de l'archive

```
archive_SHORTNAME_202603291200.zip
├── index.html              ← index du cours avec toutes les ressources
├── about.html              ← description du cours
├── shared/
│   ├── moodle.css          ← CSS autonome
│   └── icons/
│       ├── page.svg
│       └── book.svg
├── Titre--page-12.html   ← un fichier par activité Page
├── Titre--book-34/
│   ├── index.html          ← table des matières du livre
│   ├── Chapitre un--chapter-56.html
│   └── Chapitre deux--chapter-57.html
└── files/
    └── {cmid}/{filearea}/{itemid}/ ← images et fichiers embarqués
```

### Installation

1. Copier le dossier du plugin dans `{racine_moodle}/local/flatarchive/`
2. Installer les dépendances Composer :
   ```bash
   cd local/flatarchive
   composer install --no-dev
   ```
3. Aller dans **Administration du site → Notifications** pour exécuter l'installation en base
4. Attribuer la capacité `local/flatarchive:archive` au rôle Enseignant

> `vendor/` est dans `.gitignore` — toujours exécuter `composer install` sur un nouveau serveur.

### Utilisation

1. Ouvrir un cours → le lien **Flat archive** apparaît dans la navigation du cours
2. Sélectionner les Pages, Livres, Zones texte et média Fichiers, Dossiers, Glossaires et/ou Wikis à archiver
3. Cliquer sur **Générer l'archive HTML**
4. Télécharger le ZIP depuis la page de succès ; le lien reste utilisable pendant une heure dans la même session

### Prérequis

| Prérequis | Version |
|---|---|
| Moodle | 4.4+ |
| PHP | 8.1+ (selon la version de Moodle / depending on Moodle version) |
| mPDF (via Composer) | 8.2.7 |

### Types de ressources supportés

| Type | Support |
|---|---|
| `mod_page` | ✅ Complet |
| `mod_book` | ✅ Complet (table des matières + chapitres) |
| `mod_resource` | ✅ Fichier principal et fichiers associés, sans conversion |
| `mod_folder` | ✅ Description et arborescence avec fichiers originaux |
| `mod_url` | Prévu |
| `mod_label` | ✅ Texte et médias intégrés dans l’index |

### Capacité Moodle

| Capacité | Rôle par défaut |
|---|---|
| `local/flatarchive:archive` | Enseignant, Gestionnaire |

### Limitations connues

- La génération est **synchrone** — les cours très volumineux peuvent dépasser le `max_execution_time` PHP sur hébergement mutualisé
- `wait_timeout=60s` MySQL (LWS) compensé par des requêtes keepalive entre chaque module
- L’interface utilise AMD et envoie la sélection au générateur HTML synchrone. Les jobs asynchrones et le PDF restent incomplets.

---

## English

Moodle local plugin — Archive course resources (Pages, Books, Text and media areas, Files, Folders, Glossaries, Wikis, Feedbacks, Choices, Forums and Lessons) as self-contained static HTML pages bundled in a downloadable ZIP.

**Version:** 0.2.0 (2026101107) · MATURITY_ALPHA · Requires Moodle 4.4+

### What it does

Flatarchive lets teachers export selected course resources to a portable offline archive:

- Selects **Page**, **Book**, **Text and media area**, **File**, **Folder**, **Glossary**, **Wiki**, **Feedback** and **Choice** and **Forum** resources from the course tree
- Generates a **static HTML website** — no Moodle dependency after download
- Rewrites all `@@PLUGINFILE@@` links to relative paths and copies embedded files
- Adds **visibility badges** (hidden / stealth / hidden section) on each page and in the index
- Includes **module icons** (Moodle 4.x `monologo.svg`) in the index for quick identification
- Packages everything in a **ZIP** named `archive_{shortname}_{YYYYMMDDHHmm}.zip`
- Footer on every page: `Flatarchive {version} © EDUNOVER`

### Archive structure

```
archive_SHORTNAME_202603291200.zip
├── index.html              ← course index with all resources
├── about.html              ← course description
├── shared/
│   ├── moodle.css          ← standalone CSS
│   └── icons/
│       ├── page.svg
│       └── book.svg
├── Title--page-12.html         ← one file per Page activity
├── Title--book-34/
│   ├── index.html          ← book table of contents
│   ├── Chapter one--chapter-56.html
│   └── Chapter two--chapter-57.html
└── files/
    └── {cmid}/{filearea}/{itemid}/ ← embedded images & files
```

### Installation

1. Copy the plugin folder to `{moodle_root}/local/flatarchive/`
2. Install Composer dependencies:
   ```bash
   cd local/flatarchive
   composer install --no-dev
   ```
3. Visit **Site administration → Notifications** to run the DB install
4. Assign the capability `local/flatarchive:archive` to the Teacher role

> `vendor/` is git-ignored — always run `composer install` on a fresh server.

### Usage

1. Open a course → the **Flat archive** link appears in the course navigation
2. Select the Pages, Books, Text and media areas Files, Folders, Glossaries and/or Wikis to archive
3. Click **Générer l'archive HTML**
4. Download the ZIP from the success page; the link remains usable for one hour in the same session

### Requirements

| Requirement | Version |
|---|---|
| Moodle | 4.4+ |
| PHP | 8.1+ (selon la version de Moodle / depending on Moodle version) |
| mPDF (via Composer) | 8.2.7 |

### Supported resource types

| Type | Support |
|---|---|
| `mod_page` | ✅ Full |
| `mod_book` | ✅ Full (TOC + chapters) |
| `mod_resource` | ✅ Main file and companion files, without conversion |
| `mod_folder` | ✅ Description and file tree with original files |
| `mod_url` | Planned |
| `mod_label` | ✅ Text and media embedded in the course index |

### Capability

| Capability | Default role |
|---|---|
| `local/flatarchive:archive` | Teacher, Manager |

### Known limitations

- Archive generation is **synchronous** — very large courses may hit PHP `max_execution_time` on shared hosting
- MySQL `wait_timeout=60s` (LWS) mitigated by keepalive queries between modules
- The AMD interface submits the selection to the synchronous HTML generator. Background jobs and PDF support remain incomplete.

---

## License

GNU GPL v3 or later — http://www.gnu.org/copyleft/gpl.html

© 2025 [Edunover](https://edunover.eu)


## Développement et tests / Development and tests

Le build AMD est livré dans `amd/build/`. Après une modification JavaScript :
The AMD build is included in `amd/build/`. After editing JavaScript:

```bash
npm ci
npm run build
npm test
```

Les tests JavaScript vérifient la sélection, les paramètres POST, les erreurs, les cours vides,
les titres contenant du HTML et le retour navigateur, sur la source et le build livré.
JavaScript tests exercise selection, POST parameters, errors, empty courses, HTML in titles,
and browser restoration against both the source and the distributed build.

Les tests PHPUnit utilisent les générateurs et la base de test de Moodle. Depuis la racine
**d’une installation Moodle de développement dont PHPUnit est configuré**, avec ce plugin
installé dans `local/flatarchive` :
PHPUnit tests use Moodle's generators and test database. From the root of a **development
Moodle installation with PHPUnit configured**, with this plugin in `local/flatarchive`:

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit local/flatarchive/tests
```

La suite couvre Pages/Livres, ordre et noms des sections, visibilité, cours vides,
isolation des cours, droits d’accès et cours inexistant. Les réponses passent aussi par
la validation du schéma de l’API externe Moodle.
The suite covers Pages/Books, section ordering and names, visibility, empty courses,
course isolation, capabilities and missing courses, including external API response validation.

Cette suite ne valide pas encore la génération complète du ZIP ni son téléchargement.
Un essai sur Moodle reste nécessaire : Pages et Livres avec images, sélection partielle,
export, téléchargement et ouverture hors ligne du ZIP.
This suite does not yet validate complete ZIP generation or downloading. Test the full
workflow on Moodle with Pages and Books containing images, partial selection, export,
download and offline viewing.

Après installation de cette mise à jour, exécuter la mise à niveau Moodle et purger les caches.
After installing this update, run the Moodle upgrade and purge caches.


### Optimisations du build 2026093001 / Build 2026093001 improvements

- Copie des pièces jointes via l’API de fichiers Moodle, sans charger leur contenu complet dans une chaîne PHP.
- Parcours des chapitres par recordset avec fermeture garantie ; suppression d’une requête Livre inutile.
- Noms de pages/livres/chapitres avec identifiant stable et dossiers de fichiers séparés par chapitre.
- Déduplication et validation de la sélection avant toute génération ; échecs d’écriture et de ZIP signalés.
- Dossier aléatoire distinct pour chaque export. Téléchargement réservé au créateur, dans sa session,
  avec vérification des droits du cours. Les anciens liens `?path=` ne sont plus acceptés.
- Nettoyage immédiat après génération échouée. Depuis le build 2026100301, le ZIP reste
  téléchargeable pendant une heure ; les fichiers sont ensuite purgés par le nettoyage temporaire de Moodle.

Attachments use Moodle's file-copy API instead of loading complete contents into PHP strings.
Chapters use a recordset that is closed reliably. Resource IDs prevent title collisions, and
chapter attachments have separate directories. Selection is deduplicated and validated, and
write/ZIP errors are reported. Each export has its own random workspace; only its owner in the
same session can download it, subject to course permissions. Legacy `?path=` links are rejected.
Failed exports are cleaned up immediately. Since build 2026100301, downloads remain available
for one hour; Moodle scheduled temporary-file cleanup eventually removes the files.

La suite PHPUnit comprend désormais 48 tests, dont les collisions, les fichiers embarqués,
leur conservation dans un ZIP, les sessions/propriétaires, les traversées de chemins et les liens symboliques.
Ces tests nécessitent Moodle : les contrôles de syntaxe locaux ne remplacent pas leur exécution.
The PHPUnit suite now includes 48 tests covering these cases and requires a Moodle test installation.
Local syntax checks do not replace executing that suite.


## GitHub Actions

`.github/workflows/tests.yml` lance PHPUnit sur les dernières révisions des branches Moodle 4.4 à 5.3 avec MariaDB 10.11 (11.4 pour Moodle 5.3),
ainsi que les tests JavaScript et le contrôle du build AMD. Déclenchement à chaque push,
pull request ou lancement manuel. Moodle et la base sont créés dans le runner jetable :
aucun accès à l’instance sandbox ni secret serveur n’est nécessaire.

Référence : https://moodlehq.github.io/moodle-plugin-ci/

Validation GitHub du 30 septembre 2026 : installation Moodle 4.5 réussie ; le premier passage PHPUnit a révélé le chargement de l’API externe historique et un appel de nettoyage invalide. Passage aux classes `core_external` et à `fulldelete()`. Le second passage a révélé le libellé manquant de la capacité `flatarchive:archive`, ajouté en anglais, français et néerlandais ; validation réussie sur les 14 combinaisons Moodle/PHP.

Matrice CI (16 combinaisons) : Moodle 4.4 et 4.5 avec PHP 8.1/8.2/8.3 ; Moodle 5.0 et 5.1 avec PHP 8.2/8.3/8.4 ; Moodle 5.2 et 5.3 avec PHP 8.3/8.4. Chaque combinaison exécute les 72 tests PHPUnit ; les 6 tests JavaScript sont exécutés une fois. Cette matrice couvre les branches stables à jour, pas chaque ancienne version corrective.

Références des prérequis : https://moodledev.io/general/releases/4.4 ; https://moodledev.io/general/releases/4.5 ; https://moodledev.io/general/releases/5.0 ; https://moodledev.io/general/releases/5.1 ; https://moodledev.io/general/releases/5.2

## Validation du 1er octobre 2026

- GitHub Actions : 14 environnements PHPUnit et le job JavaScript réussis : https://github.com/edunover/moodle-local-flatarchive/actions/runs/36734923014 (commit `39dbae6`).
- Sandbox Moodle 4.5.11+, PHP 8.3.33 : installation du build 2026093001 réussie ; accès par le menu du cours ; sélection et génération des 50 Pages du cours TEST- réussies, sans modifier le contenu du cours.
- Téléchargement manuel réussi, confirmé par le ZIP fourni : intégrité correcte, 54 fichiers dont 50 Pages, index.html, about.html, CSS et icône. Les 203 références locales sont valides ; aucun lien externe détecté dans les attributs href/src. La récupération automatisée du navigateur intégré échoue ; les reprises de téléchargement restent à examiner, car le ZIP est supprimé après envoi.
- Aucun essai navigateur des Livres n’est encore confirmé. L’aperçu visuel local est bloqué par la politique du navigateur intégré (file:// non autorisé) ; la structure et les liens de l’archive ont été contrôlés directement.

## Prochaine version / Next release

Après génération, le lien de retour affiche « Back to Flat Archive » en anglais,
« Retour à Flat Archive » en français et « Terug naar Flat Archive » en néerlandais.
Cette modification est préparée pour la prochaine livraison, sans mise à jour de la sandbox.

After export, the return link uses a dedicated translated label in all three supported languages.
Prepared for the next delivery; not yet deployed to the sandbox.

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

Validation Forum : 67 tests PHPUnit et 579 assertions réussis sur les 16 configurations
Moodle/PHP jusqu’à Moodle 5.3 ; six tests JavaScript et standards Moodle réussis.
https://github.com/edunover/moodle-local-flatarchive/actions/runs/38144678736 (code `f450cdd`).
Le build 2026101101 est prêt pour installation et validation manuelle.

### Présentation du Forum — build 2026101102

Les discussions épinglées portent un badge traduit dans la liste et sur leur page.
Les messages sont regroupés par branche parent/réponses avec retraits et encadrés,
en conservant l’ordre chronologique entre réponses de même niveau. Le retrait visuel
est plafonné à six niveaux et réduit sur mobile ; le lien vers le parent reste disponible.
Les parents masqués ne sont pas reconstitués : leurs réponses visibles deviennent des
racines. Un parcours itératif avec détection des visites protège des références cycliques.
Deux tests supplémentaires vérifient le badge, l’ordre des branches, les profondeurs,
les parents absents et les cycles. Le fonctionnement de base du Forum est confirmé
manuellement par l’utilisateur ; ces deux améliorations restent à valider sur la sandbox.

Validation automatique du build 2026101102 : 69 tests PHPUnit sur les 16 configurations
Moodle/PHP, tests JavaScript et standards réussis :
https://github.com/edunover/moodle-local-flatarchive/actions/runs/38146037148 (code `7cdd162`).

### Tags hors connexion — build 2026101103

Les tags Moodle activés sont conservés sous forme d’étiquettes statiques, sans lien
vers le site ni exécution de filtre. L’index général affiche les tags du cours et ceux
de chaque activité sélectionnée, y compris Fichier et Texte et média. Les pages HTML
individuelles affichent les tags de leur activité ; la table des matières d’un Livre
les affiche également. Les tags de contenu restent sur le contenu correspondant.

| Activité ou ressource | Tags de l’activité | Tags du contenu |
| --- | --- | --- |
| Page | Oui | — |
| Livre | Oui | Chapitres |
| Texte et média | Oui | — |
| Fichier | Oui | — |
| Dossier | Oui | — |
| Glossaire | Oui | Entrées |
| Wiki | Oui | Pages |
| Feedback | Oui | — |
| Consultation (Choice) | Oui | — |
| Forum | Oui | Messages |

Le rendu utilise les zones natives `core/course`, `core/course_modules`,
`mod_book/book_chapters`, `mod_glossary/glossary_entries`, `mod_wiki/wiki_pages`
et `mod_forum/forum_posts`. Les tags d’un contenu masqué ne sont jamais collectés
séparément de ce contenu. Une zone désactivée ou sans tags n’affiche aucun bloc vide.
Les catégories et alias des glossaires restent distincts des tags. Aucun tag de profil
utilisateur, ni tag rattaché à un élément non exporté, n’est ajouté.

Tests : les dix types d’activités, index et pages, zones de contenu distinctes,
désactivation globale des tags, rendu sans lien en ligne et exclusion des tags de
messages supprimés. Forum épinglé et réponses imbriquées confirmés manuellement par
l’utilisateur sur le build précédent. Validation manuelle des tags encore à effectuer.

Validation automatique des tags : les 72 tests PHPUnit, six tests JavaScript et
contrôles de standards ont réussi sur la matrice Moodle 4.4 à 5.3 :
https://github.com/edunover/moodle-local-flatarchive/actions/runs/38148256222 (code `06d2a9c`).


### Leçon / Lesson — build 2026101104

Export du contenu pédagogique complet : sommaire dans l’ordre Moodle, une page HTML
par page de leçon, questions, réponses configurées, feedbacks et scores configurés
(si notation personnalisée). Les appariements distinguent les paires des feedbacks
correct/incorrect ; les compositions sont indiquées comme évaluées manuellement.
Les types natifs sont identifiés : contenu, QCM, vrai/faux, réponse courte, numérique,
appariement, composition, branche et cluster. Ce sont des corrigés consultables,
pas un moteur d’évaluation : motifs de réponse courte et plages numériques restent
les définitions configurées dans Moodle.

Les liens fixes pointent vers les pages locales : destination explicite, même page,
page suivante, précédente et fin. Sauts aléatoires, pages non vues et mode flash-card
sont signalés comme dynamiques sans simuler une tentative. Les références hors leçon
ne deviennent pas des liens. Les pages orphelines sont conservées ; les cycles sont
bornés et aucun ordre n’est réparé dans Moodle. Les liens saisis manuellement dans
le contenu vers d’autres activités restent des liens vers leur destination d’origine.

Images et fichiers des introductions, pages, réponses, feedbacks et média de leçon
sont copiés dans `files/{cmid}/{filearea}/{itemid}/`. Icône officielle et tags de
l’activité sont inclus. Aucune tentative, identité d’étudiant, note obtenue ni fichier
de composition étudiant n’est exporté. Aucun filtre de contenu n’est exécuté.

**Droits :** `local/flatarchive:archive`, `mod/lesson:view` et `mod/lesson:edit`.
Le droit d’édition est nécessaire car toutes les branches et solutions deviennent
consultables ; la leçon n’est pas proposée aux utilisateurs sans ce droit.

**English:** Lesson export preserves authored pages, solutions, feedback, local media
and fixed navigation in a static index and one HTML file per page. Full export requires
lesson view and edit permissions. Dynamic routing is described rather than executed.
Student attempts, grades and essay submissions are excluded.

Tests PHPUnit ajoutés pour la navigation, l’ordre, les médias isolés, les droits,
les destinations étrangères, les cycles, les types natifs, les flash-cards et les
leçons vides. Validation automatique réussie : 83 tests PHPUnit sur les 16 configurations
Moodle/PHP de Moodle 4.4 à 5.3, six tests JavaScript et contrôles de standards.
Exécution : https://github.com/edunover/moodle-local-flatarchive/actions/runs/38154464031
(commit de code `4dc3633`). La validation manuelle du ZIP reste à effectuer.
Fixture manuelle : cours Critical Thinking, dernière section, leçon `cmid=112`.


### Tentatives de Leçon, visibilité et navigation — build 2026101105

Le build précédent a été validé manuellement (archive du 11 octobre à 18 h 28).
Les exports de Leçon incluent maintenant les rapports individuels si l’exportateur
possède aussi `mod/lesson:viewreports`. Seuls les inscrits actifs accessibles dans
le groupe courant sont pris en compte ; groupes séparés et droits de noms complets
sont respectés avant lecture des réponses et copie des fichiers privés.

Une page par étudiant et tentative conserve réponses enregistrées (y compris essais
successifs sur une question), visites des pages de contenu, dates, état de fin et note
stockée dans la leçon. Compositions : texte, médias, commentaire de correction,
score attribué et état non encore évalué. Les notes ne sont pas recalculées et ne sont
pas présentées comme la note finale du carnet de notes. L’association des notes et
chronomètres aux numéros de tentative suit l’ordre chronologique utilisé par Moodle.
Les réponses QCM multiples et les appariements sont décodés selon leur format natif.
Les données correspondent au contenu actuel de la leçon, sans reconstitution de ses
anciennes versions. Les sauts dynamiques restent documentés, non simulés.

Dans l’index, « hidden section » figure sur le titre de section ; le badge n’est plus
répété sur chaque activité. « hidden but available » utilise la définition native
`cm_info::is_stealth()`, incluant une activité visible dans une section cachée.
Une activité réellement masquée conserve le badge « hidden ». Sur les pages isolées,
le contexte de section cachée reste indiqué. Ces badges n’altèrent aucun droit d’accès.

Chaque page HTML générée comporte un lien explicite vers l’index du cours, traduit
en français, anglais et néerlandais, avec un chemin adapté aux sous-dossiers Livre.
Les fichiers originaux joints aux ressources sont conservés sans modification.

English: permitted individual Lesson attempts now include recorded answers, essay
submissions and feedback, dates and stored marks. Hidden-section badges appear on
section headings; stealth activities use “hidden but available”. Every generated
content page provides an explicit link to the course index.

Validation automatique réussie : [GitHub Actions 38156562670](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38156562670),
17 jobs verts, 90 tests PHPUnit / 777 assertions et 6 tests JavaScript,
Moodle 4.4 à 5.3 et contrôles de standards Moodle (commit `ee1d466`).
Validation manuelle du build 2026101105 à effectuer après installation.


### Quiz — build 2026101106

Le build 2026101105 a été validé manuellement le 11 octobre (archive de 19 h 27).

L’export Quiz conserve une page d’accueil, un questionnaire réservé aux personnes
ayant le droit de gérer le test et d’utiliser les questions, puis une page par
tentative consultable. Les tentatives utilisent les versions et réponses chargées
par le moteur de questions Moodle. Les aperçus des enseignants sont exclus.
Les rapports des autres participants sont limités aux inscrits actifs, au groupe
courant et aux droits natifs de relecture ; un utilisateur sans droit de rapport
ne peut exporter que ses propres tentatives terminées si la relecture est autorisée.
Notes, corrections, solutions et feedbacks suivent les options de relecture.
Aucune tentative n’est créée en base, terminée, renotée ou modifiée par l’export.

Types pris en charge : Description, QCM, Vrai/Faux, réponse courte, numérique,
appariement et composition. Les contrôles sont convertis en texte et symboles
statiques. Les médias et pièces jointes référencés sont copiés après vérification
par le moteur de questions. Les compositions conservent le texte et les commentaires
de correction accessibles. Les points sont ceux enregistrés pour la tentative.

Les emplacements aléatoires sont signalés sans effectuer de nouveau tirage ; les
questions réellement reçues sont présentes dans les tentatives. La banque complète
n’est pas exportée. Les types non encore pris en charge (notamment Cloze, calculées,
glisser-déposer et types tiers) sont signalés ; leurs résumés enregistrés sont
conservés dans les tentatives lorsqu’ils existent. Le rendu interactif de ces types,
les historiques détaillés de chaque étape et les règles complètes des filtres de
tirage ne sont pas reproduits dans cette première version.

Chaque document conserve les liens vers l’index du cours et vers le Quiz. Le titre
de téléchargement devient « Archive générée » ; le message de succès conserve
la taille réelle du ZIP. Libellés disponibles en français, anglais et néerlandais.

English: Quiz export includes an authorised questionnaire and individual recorded
attempts, using Moodle review permissions and group restrictions. Seven core question
types have a passive offline representation. Unsupported types are identified.
Download heading is now “Archive generated”; the success message retains ZIP size.

Validation automatique réussie : [GitHub Actions 38161486385](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38161486385),
17 jobs verts, 98 tests PHPUnit / 818 assertions, 6 tests JavaScript,
Moodle 4.4 à 5.3 et contrôles de standards Moodle (commit `fbe8e27`).
Validation manuelle du Quiz à effectuer après installation du build 2026101106.


### Sélection complète du cours — build 2026101107

La page de sélection présente toutes les activités et ressources accessibles à
l’utilisateur, dans les sections et l’ordre du cours. Les types non pris en charge
conservent leur nom et leur icône officielle, avec une case désactivée et le badge
« Non pris en charge ». Les sections ne contenant que ces éléments restent visibles.
Une Leçon non exportable faute de droit d’édition affiche un motif de droits distinct.
Les contenus non accessibles selon Moodle ne sont pas révélés.

« Tout sélectionner » ignore les cases désactivées ; aucune case désactivée n’entre
dans l’envoi du formulaire. L’export reste bloqué si aucun élément n’est sélectionnable.
Les validations serveur de l’export restent actives. Libellés FR/EN/NL.

English: all accessible course activities are listed, including unsupported types
with disabled checkboxes and an explicit status. Bulk selection and form submission
exclude disabled entries. Unsupported-only sections remain visible.

Validation automatique en cours ; validation visuelle à effectuer après installation.
