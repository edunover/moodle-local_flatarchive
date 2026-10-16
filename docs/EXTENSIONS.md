# Flat Archive extension API 1 / API d’extension 1

## Français

Cette API expérimentale appartient à `0.4.0-dev`, pas à la bêta 1 publiée.
Le socle gratuit conserve ses exporteurs et ses droits d’accès existants.

### Installation et découverte

Une extension est un plugin **local Moodle séparé** (par exemple
`local_flatarchiveextras`). Son ZIP et son dépôt peuvent être distribués séparément.
Elle déclare dans son `version.php` une dépendance :

```php
$plugin->dependencies = ['local_flatarchive' => 2026101601];
```

Elle fournit `classes/flatarchive_provider.php`, classe
`local_flatarchiveextras\flatarchive_provider`, implémentant
`local_flatarchive\extension\provider`. `api_version()` retourne `1` et
`exporters()` retourne des objets implémentant `local_flatarchive\extension\exporter`.
La découverte ne charge que les plugins locaux installés (version enregistrée en base).
Un fournisseur dont la version d’API diffère est ignoré.
Deux fournisseurs revendiquant la même activité sont désactivés pour cette activité.
Une extension ne peut pas remplacer un exporteur natif.

### Éditions par branche Moodle

`module()` retourne le nom de l’activité sans `mod_`.
`branches()` retourne une liste **d’entiers explicites**, sans plage ni joker.
L’édition 405 retourne `[405]` : elle fonctionne sur toutes les versions 4.5.x,
mais pas sur 5.0.x. L’édition 500 retourne `[500]` et ne fonctionne pas sur 4.5.x.
Une liste vide désactive l’exporteur. La sélection affiche la raison du blocage.
Le serveur revérifie la branche avant d’appeler le moteur de l’extension, même
si un formulaire a été préparé avant une mise à niveau.

Cette déclaration concerne l’édition commerciale. Les métadonnées Moodle
`requires`, `supported` et `incompatible` doivent rester conformes à la compatibilité technique réelle.
Aucun serveur, minuterie, secret, preuve de paiement ou mécanisme anti-modification
n’est inclus. Le contrôle local est modifiable ; il ne garantit pas un réachat.
Le distributeur gère les achats de chaque édition. Les archives existantes sont indépendantes.

### Permissions, génération et fichiers

Le socle vérifie la visibilité, l’appartenance au cours et la capacité d’archivage.
`permitted(cm_info $cm)` doit vérifier les capacités propres à l’activité.
L’extension reste responsable des droits sur chaque réponse, groupe, rapport et fichier.
Les plugins installés exécutent du PHP de confiance : ce contrat n’est pas un bac à sable.

`render($cm, $modinfo, $directory, $options)` reçoit un dossier réservé à l’activité
(`extensions/<cmid>` dans l’espace temporaire) et les options du cours.
Il retourne `filename` (page HTML principale relative) et `filenames` (tous les fichiers relatifs).
Le socle construit lui-même le lien d’index et vérifie les chemins, l’existence des
fichiers et leur confinement réel, y compris les liens symboliques.
Les pièces jointes originales doivent être placées dans `files/` sous ce dossier.
Les pages générées utilisent les liens relatifs, `../../shared/` pour les styles
et `../../index.html` pour revenir au cours. La réorganisation finale adapte ce
lien vers `../../../index.html` et place l’ensemble sous `resources/`.
Le durcissement HTML et les classes/styles institutionnels s’appliquent aux pages générées.

L’extension génère ses paramètres spécifiques (si `settings` est sélectionné) et
ses rapports autorisés avec leurs liens internes. L’API 1 ne sérialise jamais les
paramètres tiers automatiquement et n’enregistre pas leurs rapports dans les rapports du cours.
Ne pas exporter des secrets ou des champs de base de données sans liste explicite.
Aucun exporteur commercial concret n’est livré dans ce socle.

## English

API 1 is experimental and ships in `0.4.0-dev`, not the published beta 1.
Extensions are independently installed Moodle local plugins. Declare a dependency
on `local_flatarchive` build `2026101601` and implement
`local_YOURPLUGIN\flatarchive_provider` in `classes/flatarchive_provider.php`.
The provider implements `local_flatarchive\extension\provider`, returns API version
`1`, and supplies objects implementing `local_flatarchive\extension\exporter`.
Only installed providers are discovered. API mismatches are ignored; duplicate
activity claims fail closed. Native exporters cannot be replaced.

`branches()` is an explicit integer allowlist. Edition 405 returns `[405]`, permitting
all Moodle 4.5.x patches but rejecting Moodle 5.0.x. Edition 500 returns `[500]`.
An empty list disables the exporter. Both selection and server-side dispatch enforce
this policy. This is a local edition check, not proof of purchase or tamper protection.
Keep Moodle compatibility metadata truthful and manage edition purchases separately.

The core enforces course identity, visibility and archive capability. The extension
must implement module permissions and record/group/file/report access rules.
Its renderer receives its own `extensions/<cmid>` directory and course options.
Return `filename` (main HTML document) and `filenames` (all generated relative paths).
Paths must exist and resolve inside that directory. Put original attachments in
`files/`. Use `../../shared/` for shared assets and `../../index.html` for the course
link before layout relocation. The core adds the index entry, relocates files into
`resources/`, hardens generated HTML and applies institutional styling.

Generate your own allowlisted settings (only when selected) and authorised reports
with internal navigation. API 1 does not automatically dump third-party configuration
or register third-party reports in the course report index. Installed PHP extensions
are trusted code, not sandboxed. This release includes no commercial exporter.
