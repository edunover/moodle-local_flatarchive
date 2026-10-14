# Diagrammes UML — Flat Archive

[README français](../README.fr.md) · [English README](../README.md) · [Architecture détaillée / Detailed architecture](ARCHITECTURE.md)

Ces vues décrivent le code **0.2.0, build 2026101407**, base de préparation de la bêta. Les composants proches sont regroupés pour faciliter la lecture ; ils ne représentent pas des services déployés séparément. Les diagrammes Mermaid sont affichables directement sur GitHub et leurs sources restent modifiables dans ce fichier.

These diagrams describe **0.2.0, build 2026101407**, the baseline for beta preparation. Related components are grouped for readability; they are not independently deployed services. GitHub renders the Mermaid diagrams directly from this editable Markdown file.

## 1. Composants / Components

La vue de composants UML est représentée avec la syntaxe de graphe Mermaid et le stéréotype « composant » : il s’agit d’une vue architecturale simplifiée, pas d’une notation UML complète des ports et interfaces. Les flèches indiquent une utilisation, une dépendance ou le parcours de l’artefact.

The component view uses Mermaid flowchart syntax and component stereotypes, rather than full UML port/interface notation. Arrows represent usage, dependencies or the artifact's path.

```mermaid
flowchart TB
    User["Enseignant / Teacher"]
    Admin["Administrateur / Administrator"]

    subgraph Plugin["Plugin local_flatarchive"]
        UI["«composant» Sélection / Selection<br/>index.php · selectors.js · get_tree"]
        Config["«composant» Paramètres / Settings<br/>settings.php · css_setting · appearance.js"]
        Engine["«composant» Coordination<br/>archive_process.php"]
        Selection["«composant» Validation<br/>selection"]
        Exporters["«composant» Export des activités / Activity export<br/>*_exporter · traitement Page et Livre"]
        Snapshots["«composant» Paramètres et rapports / Settings and reports<br/>settings_snapshot · reports · course_reports"]
        Rendering["«composant» Production HTML / HTML generation<br/>archive_templates · locallib<br/>icônes · tags · fichiers joints"]
        Finalisation["«composant» Finalisation<br/>layout · html_safety · appearance"]
        Temp["«composant» Archives temporaires / Temporary archives<br/>temporary_archive"]
        Download["«composant» Téléchargement / Download<br/>download.php · download_page · serve_temp.php"]
    end

    subgraph Moodle["Services Moodle / Moodle services"]
        Security["Authentification, droits et session<br/>Authentication, capabilities and session"]
        Data["Données du cours et fichiers<br/>Course data and files"]
        Settings["Configuration enregistrée / Saved configuration"]
        ZipService["Compression ZIP et stockage temporaire<br/>ZIP packer and temporary storage"]
    end

    Archive["«artefact» Archive ZIP<br/>index.html + resources/"]
    Offline["Consultation après extraction<br/>Browser viewing after extraction"]

    User --> UI
    Admin --> Config
    Config --> Settings
    UI --> Data
    UI --> Security
    UI --> Engine
    Engine --> Security
    Engine --> Selection
    Selection --> Security
    Engine --> Exporters
    Engine --> Snapshots
    Exporters --> Data
    Exporters --> Security
    Snapshots --> Data
    Snapshots --> Security
    Exporters --> Rendering
    Snapshots --> Rendering
    Engine --> Rendering
    Engine --> Finalisation
    Finalisation --> Settings
    Engine --> Temp
    Temp --> Security
    Engine --> ZipService
    ZipService --> Archive
    User --> Download
    Download --> Security
    Download --> Temp
    Download --> Archive
    Archive --> Offline
```

`archive_process.php` coordonne la génération. Les exporteurs connaissent la structure et les règles d’accès de chaque activité. Page et Livre sont encore traités directement dans le coordinateur. Les modèles communs produisent les pages ; la finalisation adapte les chemins, applique les protections HTML et ajoute les classes `flatarchive-*` et le lien vers le CSS institutionnel. `appearance::write()` prépare cette feuille de style au début de l’export.

`archive_process.php` coordinates generation. Exporters handle each activity's structure and access rules; Page and Book are still handled directly in the coordinator. Shared templates generate pages. Finalisation adapts paths, applies HTML protections and adds `flatarchive-*` classes and the institutional stylesheet link. `appearance::write()` prepares that stylesheet at the start of export.

Moodle est nécessaire à la génération, mais pas à la consultation des pages exportées. Les liens et médias distants restent dépendants de leurs destinations. Les pièces jointes originales sont conservées ; elles ne bénéficient pas du nettoyage des pages générées.

Moodle is required for generation, not for viewing exported pages. External links and remote media still depend on their destinations. Original attachments are preserved and are not sanitised like generated pages.

## 2. Séquence / Sequence

Scénario principal : sélectionner le contenu, générer l’archive, puis télécharger le ZIP. Les participants représentent des groupes de fonctions du même plugin, pas des échanges réseau entre services indépendants.

Main scenario: select content, generate the archive and download the ZIP. Participants represent groups of functions within the plugin, not separate network services.

```mermaid
sequenceDiagram
    autonumber
    actor U as Enseignant
    participant I as Interface de sélection
    participant C as Coordinateur d’export
    participant M as Services Moodle
    participant E as Exporteurs et générateurs
    participant F as Finalisation HTML
    participant T as Archives temporaires
    participant D as Téléchargement

    U->>I: Ouvrir Flat archive dans un cours
    I->>M: Vérifier connexion et droit d’archivage
    I->>M: Charger sections et activités accessibles
    M-->>I: Arborescence du cours
    I-->>U: Afficher les choix et désactiver les activités non prises en charge
    U->>I: Sélectionner activités et options du cours
    I->>C: Envoyer le formulaire POST avec sesskey
    C->>M: Vérifier connexion, droits et sesskey
    C->>C: Valider toute la sélection

    alt Requête ou sélection invalide
        C-->>U: Refuser l’export et afficher une erreur
    else Sélection valide
        C->>T: Créer un dossier temporaire et un jeton
        T-->>C: Dossier de travail et jeton
        C->>M: Lire le CSS institutionnel enregistré
        C->>C: Préparer les feuilles de style
        loop Pour chaque activité sélectionnée
            C->>E: Exporter l’activité
            E->>M: Lire contenus, fichiers et données autorisées
            M-->>E: Données accessibles
            E->>E: Générer HTML et copier les fichiers nécessaires
            E-->>C: Pages produites et entrée pour l’index
        end
        opt Option Settings sélectionnée
            C->>E: Produire les paramètres du cours et des activités
        end
        C->>E: Produire les éléments du cours cochés et autorisés
        Note over C,E: Participants, notes et rapports du cours
        C->>E: Organiser les rapports propres aux activités
        C->>C: Construire index.html avec sections et icônes
        C->>F: Finaliser les documents générés
        F->>F: Réorganiser les chemins sous resources/
        F->>F: Adapter les liens et sécuriser le HTML
        F->>F: Ajouter classes CSS et feuille institutionnelle
        F-->>C: Fichiers prêts à compresser
        C->>M: Créer le ZIP
        M-->>C: Archive créée
        C->>T: Enregistrer le jeton dans la session
        Note over T: Archive liée à l’utilisateur et à sa session<br/>Disponible pendant une heure
        C-->>U: Rediriger vers download.php
        U->>D: Afficher la page de téléchargement
        D->>T: Vérifier jeton, propriétaire et expiration
        D->>M: Vérifier les droits actuels
        D-->>U: Afficher confirmation, poids et lien
        loop Chaque téléchargement pendant la période autorisée
            U->>D: Télécharger via serve_temp.php
            D->>M: Vérifier connexion et sesskey
            D->>T: Résoudre le jeton et vérifier sa validité
            T-->>D: Chemin du ZIP existant
            D->>M: Vérifier accès au cours et droit d’archivage
            D-->>U: Envoyer le ZIP sans le supprimer
        end
    end
```

### Points de lecture / Reading notes

- **Génération synchrone :** l’export se déroule pendant la requête HTTP, sans file de tâches en arrière-plan. / **Synchronous generation:** export runs in the HTTP request, with no background job queue.
- **POST puis redirection GET :** la confirmation est séparée de la génération. Rafraîchir la page ou changer la langue ne crée pas une nouvelle archive. / **POST then GET redirect:** refreshing the confirmation page or changing its language does not regenerate the archive.
- **Sélection du cours :** décocher Settings exclut aussi les paramètres d’activité. Décocher les rapports du cours ne supprime pas les rapports des activités sélectionnées. / **Course options:** deselecting Settings also excludes activity settings; deselecting course reports does not remove selected activities' reports.
- **Échec de génération :** une exception dans le bloc de génération entraîne la suppression du dossier de travail, puis la remontée de l’erreur. Cette branche d’erreur est omise du scénario principal pour garder le schéma lisible. / **Generation failure:** an exception in the generation block deletes the workspace and propagates the error; this path is omitted from the main diagram for readability.
- **Téléchargement :** le ZIP existant est réutilisable pendant une heure dans la session propriétaire. Un jeton invalide, expiré ou un fichier absent conduit à une page d’indisponibilité ; un défaut de droits bloque l’accès. Le nettoyage temporaire Moodle récupère ensuite les fichiers, sans garantir leur effacement physique à l’instant de l’expiration. / **Download:** the owner session can retrieve the same ZIP for one hour. Invalid or expired tokens and missing files produce an unavailable page; missing capabilities block access. Moodle subsequently reclaims temporary files, not necessarily at the exact expiry time.

## Repères dans le code / Code references

| Responsabilité / Responsibility | Source |
|---|---|
| Sélection et arborescence / Selection and course tree | [index.php](../index.php), [get_tree.php](../classes/external/get_tree.php), [selection.php](../classes/archive/selection.php) |
| Coordination / Coordination | [archive_process.php](../archive_process.php) |
| Paramètres et rapports / Settings and reports | [settings_snapshot.php](../classes/archive/settings_snapshot.php), [reports.php](../classes/archive/reports.php), [course_reports.php](../classes/archive/course_reports.php) |
| Finalisation / Finalisation | [layout.php](../classes/archive/layout.php), [html_safety.php](../classes/archive/html_safety.php), [appearance.php](../classes/archive/appearance.php) |
| Durée de vie et session / Lifetime and session | [temporary_archive.php](../classes/archive/temporary_archive.php) |
| Confirmation et téléchargement / Confirmation and download | [download.php](../download.php), [download_page.php](../classes/archive/download_page.php), [serve_temp.php](../serve_temp.php) |
