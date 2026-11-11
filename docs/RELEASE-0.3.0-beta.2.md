[Français](#français) / [English](#english)

# Flat Archive 0.3.0-beta.2

## Français

Build **2026111106** · Moodle **4.4+** · Maturité **BÊTA**

> **Bêta — essais uniquement.** Logiciel fourni en l’état, sans garantie, dans les limites du droit applicable. Utilisez une instance de test et des données fictives ou anonymisées ; effectuez une sauvegarde avant installation et vérifiez les archives produites. Les limitations de responsabilité ne suppriment pas les droits impératifs. [Avertissement complet](https://github.com/edunover/moodle-local_flatarchive/blob/v0.3.0-beta.2/docs/BETA_WARNING.md#français).

### Télécharger et installer

Installez **[local_flatarchive-0.3.0-beta.2.zip](https://github.com/edunover/moodle-local_flatarchive/releases/download/v0.3.0-beta.2/local_flatarchive-0.3.0-beta.2.zip)** (également dans les fichiers en bas de page), et non les archives automatiques « Source code ». Aucun compte GitHub n’est nécessaire pour télécharger cette préversion publique. Le fichier joint `SHA256SUMS.txt` permet de vérifier l’intégrité du ZIP ; il ne constitue pas une signature numérique.

Le ZIP met à jour le même plugin `local_flatarchive` depuis la bêta.1 ou un build de correction beta.1+fix. Il n’est pas nécessaire de désinstaller le plugin. Le code d’export est identique à celui validé dans le build fix.6 ; le numéro de version et la documentation ont été actualisés pour cette livraison. Aucune nouvelle migration de base de données n’est ajoutée. La release bêta.1 et son ZIP sont conservés.

### Correctifs inclus

- **#1 — Leçon :** les descriptions `NULL` ou vides n’empêchent plus l’export.
- **#2 — Paramètres :** les restrictions d’accès multiples sont mises en forme par l’API Moodle et conservent leurs regroupements ET/OU dans l’archive.
- **#3 — Base de données :** les modèles ou le CSS vides/`NULL` sont acceptés ; les modèles de liste et de fiche absents disposent d’un affichage de secours.
- **#4 — Catégories :** les paramètres du cours affichent le chemin complet des catégories.
- **#5 — Test et antiplagiat :** le rendu statique des questions Composition évite les appels aux rapports antiplagiat, tout en conservant les réponses, fichiers et commentaires autorisés. Les réglages Compilatio ne sont pas désactivés.
- **#6 — LaTeX :** les formules courantes sont converties en MathML statique, sans JavaScript, connexion ou installation TeX côté serveur. La source est conservée ; une notation inconnue ou invalide reste en LaTeX avec un avertissement. Il s’agit d’un sous-ensemble documenté, pas d’un moteur LaTeX complet.

### Tests demandés aux testeurs LaTeX et Compilatio

**LaTeX :** reprendre les formules du cours ayant motivé le signalement, exporter avec et sans Paramètres puis consulter les pages hors ligne. Vérifier les formules en ligne et en bloc dans les Pages, zones Texte et média et descriptions. Les macros, matrices/environnements et formules séparées par des balises HTML ne sont pas converties dans cette version. Voir les [notations et limites](https://github.com/edunover/moodle-local_flatarchive/blob/v0.3.0-beta.2/docs/MATH.md#français). M01 a été validé par le mainteneur sur Moodle 4.5 avec et sans Paramètres dans le build fix.6 ; l’issue #6 est clôturée après cette validation. Un retour sur les formules originales reste bienvenu dans [#6](https://github.com/edunover/moodle-local_flatarchive/issues/6).

**Compilatio :** reproduire l’export du Test avec Compilatio activé, idéalement avec une question Composition, une réponse enregistrée et une pièce jointe. Vérifier Questionnaire et solutions, les tentatives et fichiers archivés, ainsi que le maintien des réglages Compilatio. **L’issue [#5](https://github.com/edunover/moodle-local_flatarchive/issues/5) reste ouverte : la configuration Compilatio réelle attend encore la validation du testeur.** Q01 a été validé en interne ; les tests automatisés utilisent une intégration antiplagiat simulée et ne constituent pas une validation du service Compilatio.

Le plugin principal prend en charge les activités et ressources standard Moodle documentées. Les rapports, scores et données propres à Compilatio ne sont pas exportés. IMS/SCORM et `mod_hvp` restent exclus.

### Validation et retours

La base fonctionnelle de cette livraison a passé les deux matrices CI de la PR #12 : **17 contrôles sur 17 chacune**, Moodle 4.4 à 5.3, dont `moodle-extra`. Moodle 4.5 / PHP 8.1 : **227 tests, 2 284 assertions**. [Résultats de la branche](https://github.com/edunover/moodle-local_flatarchive/actions/runs/43058332953) · [Résultats de la PR](https://github.com/edunover/moodle-local_flatarchive/actions/runs/43058345678).

Consultez le [guide de test français](https://github.com/edunover/moodle-local_flatarchive/blob/v0.3.0-beta.2/docs/BETA_TESTING.fr.md), le [README](https://github.com/edunover/moodle-local_flatarchive/blob/v0.3.0-beta.2/README.fr.md) et la [politique de sécurité](https://github.com/edunover/moodle-local_flatarchive/blob/v0.3.0-beta.2/SECURITY.md#français). Indiquez dans votre retour le build **2026111106**, les versions Moodle/PHP/navigateur, le cas testé et le résultat. Répondez dans l’issue concernée ou par courriel à [christophe@edunover.eu](mailto:christophe@edunover.eu). Ne joignez pas d’archives contenant des données personnelles ou des secrets dans les issues publiques.

---

## English

Build **2026111106** · Moodle **4.4+** · Maturity **BETA**

> **Beta — testing only.** Provided as is, without warranty to the extent permitted by applicable law. Use a test instance and synthetic or anonymised data; back up before installation and verify generated archives. Liability limitations do not exclude mandatory legal rights. [Full warning](https://github.com/edunover/moodle-local_flatarchive/blob/v0.3.0-beta.2/docs/BETA_WARNING.md#english).

### Download and install

Install **[local_flatarchive-0.3.0-beta.2.zip](https://github.com/edunover/moodle-local_flatarchive/releases/download/v0.3.0-beta.2/local_flatarchive-0.3.0-beta.2.zip)** (also attached at the bottom), not the automatic “Source code” archives. No GitHub account is needed to download this public prerelease. The attached `SHA256SUMS.txt` verifies ZIP integrity; it is not a digital signature.

The ZIP upgrades the same `local_flatarchive` plugin from beta.1 or beta.1+fix builds; do not uninstall the plugin first. Export code is identical to the validated fix.6 build; release metadata and documentation have been updated. No new database migration is added. The beta.1 release and its ZIP remain available.

### Included corrections

- **#1 — Lesson:** empty/`NULL` descriptions no longer abort export.
- **#2 — Settings:** multiple access restrictions use Moodle’s formatter and retain AND/OR groupings.
- **#3 — Database:** empty/`NULL` templates and CSS are accepted; missing list/single templates use a readable fallback.
- **#4 — Categories:** course settings show the complete category path.
- **#5 — Quiz/plagiarism integration:** static Essay rendering avoids plagiarism report callbacks while retaining authorised responses, files and comments. Compilatio settings are not disabled.
- **#6 — LaTeX:** common formulas become static MathML without JavaScript, a network connection or server TeX installation. Source is retained; unsupported/invalid formulas remain LaTeX with a notice. This is a documented subset, not a complete LaTeX engine.

### Requested LaTeX and Compilatio checks

**LaTeX:** export the original reported content with and without Settings; inspect the extracted pages offline. Check inline/display formulas in Pages, text/media areas and descriptions. Custom macros, matrices/environments and formulas split across HTML nodes are not converted in this version. See [scope and limitations](https://github.com/edunover/moodle-local_flatarchive/blob/v0.3.0-beta.2/docs/MATH.md#english). The maintainer validated M01 on Moodle 4.5 with and without Settings using fix.6; issue #6 is closed following this check. Feedback on the reporter’s original content remains welcome in [#6](https://github.com/edunover/moodle-local_flatarchive/issues/6).

**Compilatio:** reproduce the Quiz export with Compilatio enabled, ideally including an Essay question, a recorded response and an attachment. Check Questionnaire and solutions, attempts, exported files and unchanged Compilatio settings. **Issue [#5](https://github.com/edunover/moodle-local_flatarchive/issues/5) remains open pending validation on the reporter’s real Compilatio setup.** Q01 passed internally; automated tests use a simulated plagiarism integration and do not validate the Compilatio service.

The core plugin supports documented standard Moodle activities/resources. Compilatio reports, scores and proprietary data are not exported. IMS/SCORM and `mod_hvp` remain excluded.

### Validation and feedback

The functional code passed both PR #12 CI matrices: **17/17 checks each**, Moodle 4.4–5.3, including `moodle-extra`. Moodle 4.5 / PHP 8.1: **227 tests, 2,284 assertions**. [Branch results](https://github.com/edunover/moodle-local_flatarchive/actions/runs/43058332953) · [PR results](https://github.com/edunover/moodle-local_flatarchive/actions/runs/43058345678).

Read the [English test guide](https://github.com/edunover/moodle-local_flatarchive/blob/v0.3.0-beta.2/docs/BETA_TESTING.en.md), [README](https://github.com/edunover/moodle-local_flatarchive/blob/v0.3.0-beta.2/README.md) and [security policy](https://github.com/edunover/moodle-local_flatarchive/blob/v0.3.0-beta.2/SECURITY.md#english). Report build **2026111106**, Moodle/PHP/browser versions, the tested case and the outcome in the relevant issue, or email [christophe@edunover.eu](mailto:christophe@edunover.eu). Never post archives containing personal data or secrets in public issues.
