# local_flatarchive — notes techniques

Version : 0.2.0 (2026101302), MATURITY_ALPHA, Moodle 4.4 à 5.3.

## Documentation et versionnage

Maintenir README.md et claude.md à chaque évolution significative. Incrémenter
le build YYYYMMDDXX de version.php pour chaque livraison ; ne changer la release
qu’à la demande de l’utilisateur. Les sections datées ci-dessous sont historiques.

## Architecture actuelle

- `index.php`, `templates/index.mustache`, `amd/src/selectors.js` : sélection du cours.
- `classes/external/get_tree.php`, `db/services.php` : service AJAX Moodle actif via core/ajax.
- `archive_process.php` : génération HTML synchrone et assemblage du ZIP.
- `classes/archive/` : exporteurs des 16 types pris en charge, paramètres, tags,
  icônes, fichiers et téléchargements temporaires.
- `locallib.php` : visibilité, version, copie des icônes/médias et structure des sections.
- `archive_templates/` et `style/moodle.css` : documents HTML autonomes.
- `serve_temp.php` : téléchargement dans la session de création pendant une heure.
- `styles.css` : interface du plugin dans Moodle, distincte du style des archives.
- `db/access.php` : capacité `local/flatarchive:archive`.
- `db/upgrade.php` : suppression de l’ancienne table de suivi lors de la mise à jour.
  Aucune table propre au plugin n’est créée à l’installation.
- `scripts/package-plugin.py` : construction du ZIP à partir des fichiers suivis par Git.

## Conventions

- Conserver les contrôles de connexion, contexte, capacité et sesskey selon le point d’entrée.
- Vérifier les droits Moodle avant toute lecture de réponses, notes ou fichiers privés.
- Utiliser get_fast_modinfo pour la structure ; aucun changement des données durant l’export.
- JavaScript AMD via core/ajax, core/notification et core/str ; pas de fetch direct.
- Chaînes visibles traduites dans lang/en, lang/fr et lang/nl.
- Ne pas exécuter de scripts personnalisés dans les documents HTML exportés.
- `moodle-extra` est le standard PHP. Ajouter les nouveaux fichiers au périmètre CI.
- `npm ci`, `npm run build`, `npm test` pour le module AMD ; commiter amd/build.
- PHPUnit dans Moodle et matrice GitHub Actions 4.4 à 5.3.
- Pas de bibliothèque PHP tierce requise à l’exécution.

## Livraison et limites

Le ZIP est autonome ; remplacer le dossier du plugin lors d’une mise à jour manuelle.
Ne jamais inclure les dépendances de développement ou des fichiers locaux non suivis.
La génération reste synchrone, limitée par le temps et la mémoire PHP disponibles.
Les liens de téléchargement expirent après une heure ; les fichiers temporaires sont
nettoyés par Moodle. Le contenu multilingue reste un chantier distinct.

## Validation récente

L’utilisateur a validé le rendu du build 2026101207 le 12 octobre (archive de 20 h 37).
Le build 2026101208 retire le code inutilisé et teste la migration ainsi que le paquet.

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

Matrice CI (16 combinaisons) : Moodle 4.4 et 4.5 avec PHP 8.1/8.2/8.3 ; Moodle 5.0 et 5.1 avec PHP 8.2/8.3/8.4 ; Moodle 5.2 et 5.3 avec PHP 8.3/8.4. Chaque combinaison exécute les 72 tests PHPUnit ; les 6 tests JavaScript sont exécutés une fois. Cette matrice couvre les branches stables à jour, pas chaque ancienne version corrective.

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

Validation automatique réussie : [GitHub Actions 38174028369](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38174028369),
17 jobs verts, 99 tests PHPUnit / 827 assertions et 10 tests JavaScript,
Moodle 4.4 à 5.3 et contrôles des standards Moodle (commit `5ba978e`).
Validation visuelle à effectuer après installation du build 2026101107.


### Alignement des réponses du Quiz — build 2026101200

Le build 2026101107 a été validé visuellement par l’utilisateur, sans anomalie.
Les cases statiques des réponses du Quiz sont désormais alignées avec la première
ligne du libellé, dans le questionnaire et les tentatives enregistrées. Les libellés
longs et les paragraphes conservent leurs retours à la ligne à côté de la case.
Correction CSS commune aux deux vues, sans modification des réponses ou des notes.

English: static Quiz choice symbols and rich-text answer labels now share the same
line in questionnaires and recorded attempts. Long labels continue to wrap.

Validation automatique réussie : [GitHub Actions 38262748861](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38262748861),
17 jobs verts sur Moodle 4.4 à 5.3 (commit `c3c476d`).
Validation visuelle du correctif à effectuer après régénération de l’archive.


### Espacement des réponses du Quiz — build 2026101201

Les choix du questionnaire et des tentatives enregistrées sont plus compacts :
les marges extérieures des paragraphes des réponses sont supprimées et l’espace
entre les choix est réduit. Les cases restent alignées avec les libellés ; les
réponses comportant plusieurs paragraphes conservent un léger espacement interne.

English: Quiz choices use compact spacing in questionnaires and recorded attempts,
while preserving symbol alignment and spacing within multi-paragraph answers.

Validation automatique réussie : [GitHub Actions 38264188013](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38264188013),
17 jobs verts sur Moodle 4.4 à 5.3 (commit `8ba2a89`).
Validation visuelle à effectuer après installation et régénération de l’archive.


### Base de données — build 2026101202

Export complet réservé au droit Moodle `mod/data:manageentries` : toutes les fiches
et tous les groupes, y compris les fiches non approuvées (badge dans la liste et
la fiche). Les conditions étudiantes de date et de contribution ne filtrent pas
cet export. Le minimum de contributions avant consultation est indiqué lorsqu’il
est activé. Les paramètres du cours ne sont pas modifiés.

Le modèle de liste fournit l’index ; le modèle de fiche unique fournit une page
par entrée. Liens locaux, retour à la liste et à l’index du cours, champs rendus
par Moodle, fichiers locaux, auteurs, dates, tags et commentaires. Le CSS de
l’activité est conservé dans un fichier distinct. Les formulaires et commandes
d’édition sont retirés ; le JavaScript personnalisé n’est pas exporté. Les mises
en page dépendant du thème Moodle ou de services externes peuvent différer.

English: instructor-only complete Database export uses list and single-entry
templates, includes pending entries with visible badges, and copies field files.
Learner group, date and contribution restrictions do not filter this archive.
Custom CSS is retained; interactive editing controls and custom JavaScript are omitted.

Validation en attente : la première passe a identifié la suppression des balises
HTML5 de mise en page et des écarts de commentaires aux standards Moodle, corrigés
dans `f355e26`. Les vérifications locales de syntaxe PHP passent. La nouvelle
[run 38267660293](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38267660293)
n’a démarré aucun job : GitHub signale un paiement échoué ou une limite de dépenses.
Relancer PHPUnit et les standards après réactivation de GitHub Actions ; le build
2026101202 n’est pas encore validé par la matrice Moodle ni par un essai visuel.


### Correction des fiches vides — build 2026101203

Le nettoyage Moodle supprimait les identifiants temporaires du rendu,
produisant des pages vides dans le build 2026101202. Les repères utilisent
désormais des classes conservées par le nettoyage HTML.

Validation réussie : https://github.com/edunover/moodle-local-flatarchive/actions/runs/38278916063
17 jobs réussis sur Moodle 4.4 à 5.3, commit a63df75.
Installer ce build et régénérer les archives produites avec 2026101202.
La validation visuelle sur le site de test reste à effectuer.

### Paramètres archivés de la Base de données — build 2026101204

Validation manuelle reçue pour 2026101203. Le nouveau build ajoute une page locale
Paramètres / Settings / Instellingen depuis la liste de la Base de données.
Elle documente les réglages standard, les champs et les modèles de présentation
comme texte inerte. Le CSS personnalisé n’affecte pas la page documentaire.
JavaScript indiqué sans code ; configuration interne et paramètres de champs tiers
exclus. L’extension aux autres activités reste une étape ultérieure.

Validation automatique réussie : [run 38281756703](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38281756703), 17 jobs sur Moodle 4.4 à 5.3, commit `2f5a80c`. Validation visuelle de la page Paramètres à effectuer après installation et régénération de l’archive.
# Standards PHP — 12 octobre 2026

Validation : run 38284351225, commit `0298e88`, 17 jobs réussis, dont les deux
contrôles `moodle-extra`. Aucune correction PHP supplémentaire nécessaire.

Le workflow et `.phpcs.xml` utilisent désormais `moodle-extra`. Le périmètre CI
reste la liste explicite des fichiers PHP maintenus dans `tests.yml`, contrôlée
sur Moodle 5.2/5.3 avec PHP 8.4. Ne pas confondre cette validation avec un audit
de tous les anciens fichiers du dépôt. Conserver ce standard pour les prochains
développements et ajouter les nouveaux fichiers à la liste contrôlée.


### Paramètres du cours et des activités — build 2026101205

La page Paramètres de la Base de données a été validée manuellement. Les autres
activités prises en charge disposent désormais aussi d’un relevé local des réglages
standard : Page, Livre, Zone texte et média, Fichier, Dossier, Glossaire, Wiki,
Feedback, Sondage, Forum, Leçon et Test. Le lien du cours figure dans `index.html`.
Les liens d’activité sont accessibles depuis l’index et leur page principale ;
les Livres utilisent leur table des matières. Les fichiers originaux restent intacts.

Sont conservés les réglages communs (visibilité, groupes, restrictions et achèvement),
les principales options propres à chaque module, les informations de notation et
les quatre périodes de relecture du Test. Ces relevés sont documentaires et ne
reconstituent pas un formulaire d’administration Moodle. Les mots de passe ne sont
pas copiés : seule leur présence est indiquée. Les dérogations individuelles,
les secrets d’inscription et les configurations de plugins tiers sont exclus.
Les paramètres d’activité exigent `moodle/course:manageactivities`, ceux du cours
`moodle/course:update`. Les contrôles existants de la Base de données sont conservés.

English: offline settings snapshots now cover the course and all supported activity
types. Main activity pages and the course index provide local links, including
nested Book navigation. Standard options and Quiz review periods are documented;
password values, user overrides and third-party configuration are excluded.

Validation automatique réussie : [run 38286381453](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38286381453),
17 jobs verts, 110 tests PHPUnit / 969 assertions sur Moodle 5.3, tests JavaScript
et `moodle-extra` réussis. Matrice Moodle 4.4 à 5.3, commit `178f43b`.
Validation visuelle à effectuer après installation et régénération de l’archive.


### Lisibilité des paramètres et descriptions — build 2026101206

Les liens Paramètres de l’index ont une bordure et un espacement distincts du titre.
Les tags d’activité ne sont plus ajoutés à l’index du cours ; ils restent présents
sur les pages de paramètres. Les tags propres aux contenus sont conservés.

Les paramètres comprennent toujours une section Description : introduction de
l’activité ou résumé du cours, avec copie des médias locaux. Un champ vide, y compris
un paragraphe d’éditeur vide, affiche « Champ vide » (FR/EN/NL). Une image seule reste
un contenu valide. La même convention s’applique aux paramètres de la Base de données.

English: index Settings links are visually separated from activity titles. Activity
tags remain on settings pages without being repeated in the course index. Settings
include the activity description or course summary, local media and an explicit
empty-field indication when appropriate.

Validation automatique réussie : [run 38289110859](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38289110859),
17 jobs verts sur Moodle 4.4 à 5.3, dont `moodle-extra`. Moodle 5.3 : 111 tests
PHPUnit / 1 027 assertions. Commit `36f972f`. Validation visuelle à effectuer
après installation et régénération de l’archive.


### Description en tête des paramètres — build 2026101207

La description précède maintenant le relevé daté et les tableaux de réglages,
pour le cours et toutes les activités, Base de données comprise. Les indications
de champ vide et les médias locaux sont conservés.

English: descriptions now precede snapshot metadata and settings tables for the
course and all supported activities, including Database.

Validation : [run 38290709056](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38290709056),
17 jobs verts sur Moodle 4.4 à 5.3, commit `c012e40`.

Validation du build 2026101208 : [run 38292832714](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38292832714),
17 jobs réussis sur Moodle 4.4 à 5.3, incluant la migration, `moodle-extra` et
la vérification du ZIP installable. Commit `a50f3b4`.


### Racine simplifiée de l’archive — build 2026101209

Seul `index.html` reste à la racine, accompagné du dossier `resources/` qui regroupe
les autres pages, paramètres, styles, icônes et fichiers joints. Les liens locaux
sont recalculés, y compris les retours à l’index depuis les sous-dossiers Livre.
Les pièces jointes originales ne sont pas modifiées ; les liens externes restent externes.
Conserver le fichier index.html et le dossier resources ensemble lors d’une copie.

English: the archive root contains only index.html and resources/. Generated HTML
links are adjusted, including nested Book navigation. Original attachments remain
byte-identical. Keep both root items together when moving or sharing the archive.

Contrôle local sur l’archive utilisateur du 12 octobre à 20 h 53 : 106 fichiers,
455 liens locaux valides, 6 fichiers joints identiques octet pour octet.

Validation : [run 38294485057](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38294485057),
17 jobs verts sur Moodle 4.4 à 5.3, commit `933217c`. Tests d’extraction ZIP,
navigation et conservation des fichiers originaux réussis.

## URL et Devoir — build 2026101210

Le build 2026101209 (index seul à la racine) a été validé manuellement, RAS.

- URL : description, médias et lien externe conservés ; aucune copie du site distant.
  Les paramètres dynamiques personnels Moodle ne sont pas résolus.
- Devoir : consignes, fichiers de référence, texte en ligne, fichiers remis, historique
  des tentatives, notes du devoir et feedbacks texte/fichiers. Rapports réservés à
  `mod/assign:grade`, avec participants Moodle, groupes séparés, allocations de correction
  et anonymat avant révélation des identités. Les fichiers remis restent inchangés :
  leur propre contenu peut identifier leur auteur, comme dans Moodle.
- Remises de groupe : contenu commun, notes individuelles. Les notes sont celles du
  devoir, pas les éventuelles dérogations du carnet de notes.
- Grilles avancées, annotations PDF, commentaires de remise et extensions tierces
  non exportés ; indication des extensions actives non prises en charge.
- Settings : adresse et affichage URL ; dates, groupes de remise, anonymat, workflow
  et nombre de tentatives du devoir, en plus des paramètres communs.
- PHPUnit : liens URL et médias, protocoles dangereux, deux tentatives, feedbacks,
  absence de données privées sans droit de correction, anonymat et groupes.

Validation automatique réussie : [run 38297358444](https://github.com/edunover/moodle-local-flatarchive/actions/runs/38297358444), 17/17 jobs verts, Moodle 4.4 à 5.3, JavaScript, paquet et `moodle-extra`. Code validé : `bb5149c`. Validation manuelle du build 2026101210 à effectuer après installation.


## H5P natif — build 2026101301

- `h5p_package` lit les JSON et extrait uniquement les médias passifs d’un paquet
  `mod_h5pactivity/package/0`, sans déployer de bibliothèque H5P ni modifier Moodle.
  Le paquet original reste dans `files/{cmid}/h5p/0/source.h5p` ; médias dans `media/`.
- `h5p_renderer` convertit les bibliothèques vers du HTML nettoyé. Les contenus
  imbriqués sont bornés à 16 niveaux. Type/version majeure inconnue : avertissement.
  Les pages de présentation sont linéarisées et ne visent pas une copie pixel à pixel.
- `h5p_exporter` produit la page principale, les pages de questions/diapositives et
  les tentatives historiques autorisées. Contrôle manageactivities pour les solutions ;
  reviewattempts + gestionnaire natif Moodle pour chaque rapport (groupes/inscription).
  Aucun calcul de score, ni nouvelle tentative, ni consultation enregistrée.
- Sélection AJAX, dispatch, settings, FR/EN/NL, CSS et périmètre moodle-extra étendus.
- Tests : cinq types, navigation, médias et intégrité du paquet, HTML inerte,
  chemins traversants, rapports, groupes, suivi désactivé et droits d’auteur de contenu.
- Le build précédent 2026101210 a été validé manuellement par l’utilisateur, RAS.
- Validation : https://github.com/edunover/moodle-local-flatarchive/actions/runs/38432938448
  17/17 jobs verts ; 128 tests PHPUnit / 1 176 assertions sur Moodle 5.3.
  Code `d16b995`, standards moodle-extra et paquet vérifiés.
  PHPUnit signale 27 dépréciations et une notice sur Moodle 5.3, sans échec.
  Validation manuelle avec les paquets H5P de la sandbox à effectuer.


## Correctif des indices H5P Summary — build 2026101302

Un paquet réel Course Presentation contient un indice Summary sous forme de texte,
alors que le convertisseur attendait un groupe de champs. Cela interrompait la
création de l’archive avec une TypeError dans `h5p_renderer::feedback()`.
L’indice texte est maintenant normalisé avant le rendu ; les groupes, indices vides
et absents restent pris en charge. Un test reproduit ces variantes dans une vraie
chaîne d’export Course Presentation, avec conservation des solutions et nettoyage HTML.

English: Summary hints stored as plain strings no longer interrupt Presentation
exports. Both string and grouped hints are supported, including empty/missing values.
Validation automatique en cours ; régénérer l’archive après installation du correctif.
