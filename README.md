# local_flatarchive

> [Français](#français) | [English](#english)

---

## Français

Plugin local Moodle — Archive les ressources d'un cours (Pages & Livres) en pages HTML statiques autonomes regroupées dans un ZIP téléchargeable.

**Version :** 0.2.0 (2026032903) · MATURITY_ALPHA · Requiert Moodle 4.4+

### Fonctionnalités

Flatarchive permet aux enseignants d'exporter des ressources de cours vers une archive portable utilisable hors ligne :

- Sélection des activités **Page** et **Livre** depuis l'arborescence du cours
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
├── Titre de la page.html   ← un fichier par activité Page
├── Titre du livre/
│   ├── index.html          ← table des matières du livre
│   ├── Chapitre un.html
│   └── Chapitre deux.html
└── files/
    └── {cmid}/             ← images et fichiers embarqués
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
2. Sélectionner les Pages et/ou Livres à archiver
3. Cliquer sur **Générer l'archive HTML**
4. Télécharger le ZIP depuis la page de succès

### Prérequis

| Prérequis | Version |
|---|---|
| Moodle | 4.4+ |
| PHP | 7.4+ |
| mPDF (via Composer) | 8.2.7 |

### Types de ressources supportés

| Type | Support |
|---|---|
| `mod_page` | ✅ Complet |
| `mod_book` | ✅ Complet (table des matières + chapitres) |
| `mod_resource` | Prévu |
| `mod_url` | Prévu |
| `mod_label` | Prévu |

### Capacité Moodle

| Capacité | Rôle par défaut |
|---|---|
| `local/flatarchive:archive` | Enseignant, Gestionnaire |

### Limitations connues

- La génération est **synchrone** — les cours très volumineux peuvent dépasser le `max_execution_time` PHP sur hébergement mutualisé
- `wait_timeout=60s` MySQL (LWS) compensé par des requêtes keepalive entre chaque module
- L'interface JS utilise un script non-AMD (migration AMD en cours)

---

## English

Moodle local plugin — Archive course resources (Pages & Books) as self-contained static HTML pages bundled in a downloadable ZIP.

**Version:** 0.2.0 (2026032903) · MATURITY_ALPHA · Requires Moodle 4.4+

### What it does

Flatarchive lets teachers export selected course resources to a portable offline archive:

- Selects **Page** and **Book** activities from the course tree
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
├── Page title.html         ← one file per Page activity
├── Book title/
│   ├── index.html          ← book table of contents
│   ├── Chapter one.html
│   └── Chapter two.html
└── files/
    └── {cmid}/             ← embedded images & files
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
2. Select the Pages and/or Books to archive
3. Click **Générer l'archive HTML**
4. Download the ZIP from the success page

### Requirements

| Requirement | Version |
|---|---|
| Moodle | 4.4+ |
| PHP | 7.4+ |
| mPDF (via Composer) | 8.2.7 |

### Supported resource types

| Type | Support |
|---|---|
| `mod_page` | ✅ Full |
| `mod_book` | ✅ Full (TOC + chapters) |
| `mod_resource` | Planned |
| `mod_url` | Planned |
| `mod_label` | Planned |

### Capability

| Capability | Default role |
|---|---|
| `local/flatarchive:archive` | Teacher, Manager |

### Known limitations

- Archive generation is **synchronous** — very large courses may hit PHP `max_execution_time` on shared hosting
- MySQL `wait_timeout=60s` (LWS) mitigated by keepalive queries between modules
- JS interface uses a non-AMD script (AMD migration pending)

---

## License

GNU GPL v3 or later — http://www.gnu.org/copyleft/gpl.html

© 2025 [Edunover](https://edunover.eu)
