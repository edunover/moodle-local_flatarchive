# Guide de test — Flat Archive 0.3.0-beta.1

[English](BETA_TESTING.en.md) · [README](../README.fr.md)

Build **2026101600**, maturité **Bêta**. Cette première campagne vise un petit groupe de testeurs aux cours, versions Moodle et rôles variés. La préversion est publique ; l’invitation organise les retours, elle ne limite pas techniquement l’accès au téléchargement.

## Préparer votre environnement

Utilisez une instance de test, des comptes fictifs et des données synthétiques ou anonymisées. Conservez une sauvegarde de l’instance avant toute installation ou mise à jour. Notez les versions exactes de Moodle, PHP, du plugin, le thème, le format du cours et les plugins impliqués. Le minimum déclaré est Moodle 4.4 ; la matrice automatisée est détaillée dans le workflow du dépôt.

Téléchargez `local_flatarchive-0.3.0-beta.1.zip` depuis la préversion GitHub. Le fichier `SHA256SUMS.txt` permet de contrôler son intégrité avec `shasum -a 256 local_flatarchive-0.3.0-beta.1.zip` (macOS) ou `sha256sum` (Linux). Comparez le résultat au fichier publié. Ce contrôle n’est pas une signature numérique.

Les archives peuvent contenir des données personnelles. Ne joignez jamais une archive réelle, des noms d’étudiants, notes, remises, mots de passe, jetons ou clés de session à une issue publique. Ne publiez pas une archive sur le domaine authentifié de Moodle.

## Parcours de validation

Pour chaque cas, consignez : **Réussi / Échec / Non testé**, le résultat obtenu et les étapes de reproduction. « Non testé » ne constitue pas une validation.

| Cas | Manipulation | Résultat attendu |
|---|---|---|
| Installation neuve | Installez le ZIP via l’installateur Moodle sur une instance sans Flat Archive. | Installation terminée, version 0.3.0-beta.1, accès à Flat Archive et aux paramètres du plugin. |
| Mise à jour | Sur une autre instance de test ou une copie, mettez à jour depuis 0.2.0, idéalement build 2026101500. | Cours préservés, CSS enregistré conservé, mise à jour terminée. |
| Paramètres | Ouvrez Administration du site → Plugins → Plugins locaux → Flat archive, ou le lien administrateur de la page de sélection. | Page accessible ; certains écrans de liste des plugins locaux ne proposent pas ce lien. |
| Styles | Choisissez chacun des trois modèles, puis Aucun ; modifiez et enregistrez le CSS. Rouvrez la page. | Le choix remplit le champ ; les modifications enregistrées sont conservées ; le style s’applique aux nouvelles archives. |
| Sélection | Sélectionnez quelques activités, puis utilisez Tout sélectionner/Tout désélectionner. | Activités non prises en charge désactivées ; options du cours également sélectionnables. |
| Options du cours | Exportez avec et sans Paramètres, Participants, Notes et Rapports ; faites aussi un export limité au cours. | Les pages correspondent aux choix et aux droits. Décocher Paramètres exclut ceux du cours, des sections et des activités. |
| Sections | Utilisez une section vide, cachée, avec description et image, puis avec restriction d’accès. | Paramètres des sections accessibles, médias locaux, badges et liens de retour corrects. |
| Contenus | Essayez les modules présents dans votre cours, avec fichiers, images, liens et caractères accentués. | Contenus consultables ; limites propres à chaque module conformes au README. |
| Rapports | Comparez les données exportées avec celles accessibles dans Moodle sous le même compte. | Rapports du cours et d’activité séparés ; anonymat, groupes et restrictions de notes respectés. |
| Rôles | Répétez avec un enseignant éditeur et un compte aux droits réduits, pas seulement un administrateur. | Les droits effectifs déterminent les éléments accessibles ; un compte sans droit d’archivage est refusé. |
| Téléchargement | Cliquez deux fois sur Télécharger dans l’heure, puis changez la langue de la confirmation. | Même ZIP disponible, pas de nouvelle génération ni d’erreur liée au second téléchargement. |
| Expiration | Réessayez après une heure, ou depuis une autre session de test. | L’ancien lien n’autorise pas le téléchargement hors de sa session ou de sa période de validité. |
| Consultation hors ligne | Extrayez tout le ZIP, coupez la connexion et ouvrez index.html dans votre navigateur. | Pages, CSS et médias locaux accessibles ; seuls index.html et resources/ sont à la racine. Les liens externes ne sont pas garantis hors ligne. |
| Navigation | Parcourez index, activités, paramètres, rapports et pages imbriquées. | Liens internes et retours fonctionnels, aucune chaîne de traduction sous forme de doubles crochets. |
| Pied de page | Consultez plusieurs types de pages. | Version, build et maturité Bêta affichés dans la langue d’export. |
| Volume | Exportez progressivement des cours plus volumineux sur l’instance de test. | Notez durée, taille ZIP et éventuel message d’erreur ; aucune durée ou taille maximale n’est garantie. |

## Limites connues

- Archive de consultation, pas une sauvegarde restaurable ni un lecteur d’activités interactives. Les notes ne sont pas recalculées.
- IMS/SCORM et `mod_hvp` exclus. H5P natif : Course Presentation, Interactive Video, Question Set, Drag and Drop, Drag the Words, avec les éléments imbriqués pris en charge.
- Génération synchrone : les limites de mémoire, temps et stockage du serveur peuvent affecter les gros cours.
- Paramètres documentaires sélectionnés ; options spécifiques aux formats de cours tiers exclues.
- Liens et médias externes restent dépendants de leurs fournisseurs. Les pièces jointes originales ne sont pas désinfectées.
- La maturité Bêta n’est pas une garantie de conformité réglementaire, de conservation à durée illimitée ou d’absence de vulnérabilités. Consultez [SECURITY.md](../SECURITY.md).

## Transmettre un retour

Ouvrez une [issue GitHub](https://github.com/edunover/moodle-local_flatarchive/issues/new). Utilisez le formulaire de retour bêta s’il est proposé. Les formulaires GitHub dépendent de la branche par défaut ; tant que la branche de préparation n’y est pas intégrée, copiez le modèle ci-dessous dans une issue ordinaire.

```text
Version/build du plugin :
Moodle / PHP / navigateur :
Thème / format de cours / plugins concernés :
Rôle et droits particuliers (sans identifiant personnel) :
Cas testé et statut (Réussi / Échec / Non testé) :
Étapes de reproduction avec données fictives :
Résultat attendu :
Résultat obtenu :
Durée d’export / taille ZIP / volume approximatif du cours :
Message d’erreur nettoyé des données personnelles et secrets :
```

Un retour réussi est utile : indiquez également les types d’activités effectivement testés. Pour une vulnérabilité, suivez le canal privé décrit dans SECURITY.md ; ne publiez pas les détails d’exploitation dans une issue publique.
