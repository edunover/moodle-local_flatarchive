# Flat Archive pour Moodle

[English](README.md) | **Français**

`local_flatarchive` crée un instantané HTML statique et portable des contenus sélectionnés dans un cours. Extrayez le ZIP et ouvrez `index.html` ; les autres pages et fichiers se trouvent dans `resources/`. Il s’agit d’une archive de consultation, **pas d’un format de sauvegarde ou de restauration Moodle**.

Version actuelle du code : **0.2.0 / build 2026101407**, maturité **alpha**. Moodle minimum : **4.4**. La configuration d’intégration continue couvre Moodle 4.4 à 5.3 avec les versions PHP définies dans la matrice ; cette configuration ne prouve pas que chaque exécution a réussi. Consultez les [exécutions des tests](https://github.com/edunover/moodle-local_flatarchive/actions/workflows/tests.yml).

## Installation et utilisation

1. Installez le ZIP du plugin avec l’installateur Moodle, ou placez son dossier `flatarchive` dans `local/flatarchive`, puis terminez la mise à jour Moodle.
2. Ouvrez un cours → Plus → Flat archive. Les gestionnaires et enseignants éditeurs reçoivent par défaut la capacité `local/flatarchive:archive`.
3. Sélectionnez les activités accessibles et prises en charge, puis générez l’archive. Les activités non prises en charge restent visibles, mais ne sont pas sélectionnables.
4. Téléchargez le ZIP. Plusieurs téléchargements sont possibles pendant une heure dans la même session authentifiée. Le nettoyage des fichiers temporaires de Moodle récupère ensuite les fichiers sur disque ; l’expiration ne garantit pas leur effacement physique immédiat.
5. Extrayez la totalité du ZIP et ouvrez `index.html`. Conservez le dossier `resources/` à côté de ce fichier.

La page de sélection comprend une partie Cours avec Paramètres, Participants, Notes et Rapports, cochés par défaut. Ces options contrôlent séparément les pages du cours. Décocher Paramètres exclut aussi les paramètres des activités, y compris ceux de la Base de données. Les rapports d’activité restent associés aux activités sélectionnées lorsque les rapports du cours sont désactivés. Un export limité aux éléments du cours est possible. Tout sélectionner/Tout désélectionner agit sur les options du cours et les activités sélectionnables.

Après une génération réussie, la requête POST redirige vers `download.php`, une page de confirmation en lecture seule. Changer la langue ou actualiser cette page conserve le même ZIP lié à la session et sa date d’expiration initiale. Seule la langue de l’interface change ; le contenu de l’archive conserve la langue utilisée lors de l’export. Une archive expirée ou supprimée par le nettoyage est signalée comme indisponible.

**Aucune installation de Composer, mPDF, Node.js, CDN ou lecteur JavaScript n’est nécessaire pour consulter les pages générées.** Node.js et Composer servent uniquement aux outils de développement et de test. L’interface de sélection du plugin utilise le chargeur JavaScript AMD de Moodle.

## Activités et ressources prises en charge

| Module Moodle | Contenu archivé et principales limites |
|---|---|
| Page (`page`) | Contenu mis en forme et fichiers Moodle intégrés. |
| Livre (`book`) | Table des matières et pages des chapitres ; les chapitres cachés nécessitent le droit Moodle correspondant. |
| Zone texte et média (`label`) | Contenu et médias intégrés dans l’index du cours. |
| Fichier (`resource`) | Fichier principal et fichiers associés, conservés à l’identique. |
| Dossier (`folder`) | Arborescence navigable et fichiers originaux. |
| URL (`url`) | Description et lien externe ; le site de destination n’est pas téléchargé. |
| Glossaire (`glossary`) | Articles approuvés, synonymes, catégories, auteurs, commentaires accessibles et pièces jointes. Articles en attente et évaluations exclus. |
| Wiki (`wiki`) | Dernières versions accessibles des pages, liens locaux, fichiers, auteurs et commentaires accessibles. Pas d’historique des versions. |
| Feedback (`feedback`) | Questionnaire et réponses individuelles soumises accessibles. Pas de brouillons ; identité et date omises pour les réponses anonymes. |
| Sondage (`choice`) | Options et résultats autorisés par les paramètres de publication, les droits de rapport et le périmètre des groupes. |
| Forum (`forum`) | Discussions et messages accessibles, état épinglé, réponses imbriquées, auteurs et pièces jointes. |
| Leçon (`lesson`) | Contenu pédagogique, réponses et solutions, rapports de tentatives accessibles ; droit de modification requis. La navigation dynamique est décrite. |
| Test (`quiz`) | Questionnaire et solutions pour les utilisateurs autorisés à gérer le test ; tentatives accessibles selon les règles Moodle de relecture et de rapport. Aucun nouveau tirage aléatoire ni calcul de note. |
| Base de données (`data`) | Modèles de liste et de fiche unique, fichiers, fiches approuvées ou non avec badges. Gestion des fiches requise ; le seuil minimal de contributions est documenté plutôt qu’appliqué. JavaScript personnalisé omis. |
| Devoir (`assign`) | Consignes, remises prises en charge, notes enregistrées, commentaires et fichiers de feedback. Pas de rendu des formulaires d’évaluation avancée ni des annotations PDF. |
| H5P (`h5pactivity`) | Rendu statique de Course Presentation, Interactive Video, Question Set, Drag and Drop et Drag the Words ; éléments imbriqués pris en charge, solutions et rapports enregistrés disponibles. Fichier `.h5p` original conservé. Aucun lecteur interactif. `mod_hvp` non pris en charge. |
| Atelier (`workshop`) | Consignes, formulaires d’évaluation natifs, exemples, travaux, évaluations et notes accessibles. Stratégies : évaluation cumulative, commentaires, nombre d’erreurs et grille critériée. Les scores enregistrés ne sont pas recalculés. |

Les paquets de contenu IMS et SCORM ne font pas partie du périmètre pris en charge.

Les paramètres du cours et des activités constituent des **instantanés documentaires de champs explicitement sélectionnés**, pas des copies exhaustives de configuration. Ils comprennent les descriptions, même vides, la visibilité, les tags d’activité et les paramètres pris en charge. Mots de passe, secrets, dérogations utilisateur et configurations arbitraires de plugins tiers sont exclus. Les tags du cours et des contenus sont conservés lorsqu’ils sont pris en charge. Chaque page de contenu générée permet de revenir à l’index du cours.

Les exports respectent les capacités Moodle actuelles, la visibilité et les règles de rapport propres aux exporteurs. Ils peuvent contenir des noms d’étudiants, des travaux et des notes. Une fois exporté, le ZIP ne bénéficie plus du contrôle d’accès ni d’un mécanisme de révocation Moodle : adaptez son stockage et son partage.

## Rapports

La navigation de l’index du cours est **Paramètres · Participants · Carnet de notes · Rapports**. Participants et Carnet de notes apparaissent uniquement lorsque les droits le permettent. La liste des participants comprend les utilisateurs inscrits, y compris les inscriptions inactives, les rôles effectifs avec leurs noms propres au cours et l’état de l’inscription, dans le périmètre du groupe sélectionné. Elle nécessite `moodle/course:viewparticipants`. Le Carnet de notes ouvre directement les notes enregistrées. Rapports ouvre un index local réservé aux rapports du cours :

- **Rapports du cours :** achèvement du cours avec critères, achèvement des activités et comptages de participation. Ils couvrent l’ensemble du cours accessible, indépendamment des contenus sélectionnés. Chaque page indique sa date de génération et le périmètre des groupes, avec des liens de retour aux rapports et à l’index du cours.

Les **rapports d’activité** appartiennent à leur activité, pas à l’index des rapports du cours. Pour Test, Leçon, Feedback, Sondage, Devoir, H5P et Atelier, **Paramètres · Rapports** figure sur une même ligne de navigation. Rapports ouvre une page locale dédiée à l’activité et au type de rapport, contenant les résultats autorisés ou un message explicite d’absence de résultats. Le contenu est déplacé, pas dupliqué ; les solutions des questions ne sont pas des tentatives d’étudiants. Les pages de détail conservent leurs liens de retour à l’activité.

Chaque rapport du cours exige sa capacité Moodle native ; les notes exigent également `moodle/grade:viewall`. Les rapports inaccessibles sont signalés sans lien. La population des rapports repose sur les inscriptions actives : rôles configurés pour le carnet de notes dans les rapports de notes et de participation, utilisateurs suivis par le système natif pour l’achèvement. Elle diffère de l’annuaire Participants, qui comprend aussi les inscriptions inactives. Les restrictions de groupes séparés et le groupe courant du cours s’appliquent. Sans `moodle/grade:viewhidden`, les notes cachées sont omises, ainsi que les totaux du cours et des catégories pour éviter une divulgation indirecte. Les notes enregistrées ne sont pas recalculées.

La participation utilise le lecteur SQL de journaux activé préféré par Moodle, les événements web/services web conservés pour les participants jusqu’à l’instantané et les secondes distinctes par catégorie de consultation ou contribution. Les événements anonymes nécessitent le droit natif correspondant. Adresses IP, données brutes des événements, journaux purgés, anciens journaux et stockages externes sont exclus. L’absence de lecteur compatible est signalée. Cette version n’exporte pas tous les rapports personnalisés installés, les journaux en direct, les statistiques ou les analyses : elle prend en charge le carnet de notes et les trois rapports du cours décrits ci-dessus.

## Contenu statique et limites de sécurité

Le HTML généré est nettoyé avec les API Moodle et finalisé avec une politique de sécurité du contenu restrictive (CSP). Les scripts et formulaires ne permettent pas d’exécuter les activités de manière interactive ; les applications externes intégrées deviennent des liens. La lecture native d’images, de sons et de vidéos reste possible. Les médias distants et les liens externes peuvent nécessiter Internet.

Les pièces jointes originales, dont les documents HTML, SVG et Office et les paquets H5P sources, sont conservées sans désinfection. Elles ne relèvent pas de la politique des pages générées. Le plugin n’est ni un antivirus, ni un environnement isolé d’exécution, ni une garantie contre toutes les attaques. Consultez [SECURITY.md](SECURITY.md) avant le déploiement.

Les marqueurs multilingues tels que `{mlang}` et `{multilang xx}` de FilterCodes sont conservés comme contenu source ; aucune navigation supplémentaire entre langues n’est générée. La restitution fidèle de tous les types de questions tiers, plugins de champs ou applications intégrées n’est pas garantie.

## Développement et vérification

```sh
npm ci
npm test
npm run build
git diff --exit-code -- amd/build
phpcs --standard=.phpcs.xml --extensions=php .
python3 scripts/package-plugin.py --output /tmp/local_flatarchive.zip
```

PHPCS nécessite l’installation de `moodle-extra`. PHPUnit exige une installation Moodle de test et une base de données séparées : utilisez le workflow `.github/workflows/tests.yml` ou `moodle-plugin-ci phpunit --fail-on-warning` dans son environnement configuré. Ne dirigez jamais PHPUnit vers une base de production. Le générateur de paquet inclut uniquement les fichiers d’exécution et de documentation suivis par Git : ajoutez les nouveaux fichiers à l’index Git avant de construire le paquet.

Documentation technique complémentaire en anglais :

- [Diagrammes UML de composants et de séquence (FR/EN)](docs/UML.md)
- [Architecture et responsabilités du code](docs/ARCHITECTURE.md)
- [Dépendances d’exécution, de développement et d’intégration continue](docs/DEPENDENCIES.md)
- [Modèle de sécurité et signalement](SECURITY.md)
- [Compte rendu de revue et de vérification du 13 octobre 2026](docs/AUDIT-2026-10-13.md)

## Style institutionnel

Les paramètres du plugin proposent trois styles CSS de départ modifiables : Bleu institutionnel, Vert moderne et Typographie classique, ainsi que Aucun. Le CSS enregistré est intégré comme feuille de style locale aux pages générées. Tous les éléments du corps des pages reçoivent des classes `flatarchive-*`. Consultez la [configuration de l’apparence et la référence des sélecteurs](docs/APPEARANCE.md), en anglais.

## Licence

Code du plugin : GNU GPL v3 ou ultérieure, Edunover. Les contenus de cours et pièces jointes conservent leurs propres droits. Les icônes sont copiées depuis les modules Moodle installés. Consultez les licences individuelles des outils de développement dans le fichier de verrouillage npm et leurs distributions.
