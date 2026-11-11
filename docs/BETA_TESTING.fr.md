# Guide de test — Flat Archive 0.3.0-beta.2

> **Bêta — essais uniquement.** Logiciel fourni en l’état, sans garantie, dans les limites du droit applicable. Utilisez une instance de test et des données fictives ou anonymisées ; effectuez une sauvegarde avant installation et vérifiez les archives produites. Les limitations de responsabilité ne suppriment pas les droits impératifs. [FR/EN](BETA_WARNING.md).

[English](BETA_TESTING.en.md) · [README](../README.fr.md)

Build **2026111106**, maturité **Bêta**. Cette deuxième bêta regroupe les correctifs des issues #1 à #6 et sollicite une validation ciblée LaTeX et Compilatio. La préversion est publique ; l’invitation organise les retours, elle ne limite pas techniquement l’accès au téléchargement.

## Préparer votre environnement

Utilisez une instance de test, des comptes fictifs et des données synthétiques ou anonymisées. Conservez une sauvegarde de l’instance avant toute installation ou mise à jour. Notez les versions exactes de Moodle, PHP, du plugin, le thème, le format du cours et les plugins impliqués. Le minimum déclaré est Moodle 4.4 ; la matrice automatisée est détaillée dans le workflow du dépôt.

Téléchargez `local_flatarchive-0.3.0-beta.2.zip` depuis la préversion GitHub. Le fichier `SHA256SUMS.txt` permet de contrôler son intégrité avec `shasum -a 256 local_flatarchive-0.3.0-beta.2.zip` (macOS) ou `sha256sum` (Linux). Comparez le résultat au fichier publié. Ce contrôle n’est pas une signature numérique.

Les archives peuvent contenir des données personnelles. Ne joignez jamais une archive réelle, des noms d’étudiants, notes, remises, mots de passe, jetons ou clés de session à une issue publique. Ne publiez pas une archive sur le domaine authentifié de Moodle.

## Vérifications ciblées de la bêta.2

- **LaTeX — issue #6 :** reprenez le contenu du signalement, avec des formules en ligne et en bloc dans une Page, une zone Texte et média et une description d’activité. Exportez avec et sans Paramètres, puis consultez l’archive décompressée hors ligne. Les notations courantes doivent apparaître en MathML statique ; une notation non prise en charge doit conserver sa source complète avec un avertissement. Précisez le navigateur et sa version, et une formule non sensible en cas de différence. M01 a été validé par le mainteneur sur Moodle 4.5 avec et sans Paramètres dans le build fix.6 ; une confirmation sur le contenu du testeur reste bienvenue. Consultez le [périmètre mathématique](MATH.md).
- **Test + Compilatio — issue #5 :** sur un site de test où Compilatio est activé pour le Test, reproduisez l’export initial. Incluez une question Composition native et, si possible, une tentative contenant une réponse et une pièce jointe. Vérifiez Questionnaire et solutions, les réponses enregistrées, les fichiers et commentaires de correction autorisés. L’archive doit se générer sans exception liée à l’identifiant d’utilisation 0 ; les réglages Compilatio doivent rester inchangés. **La validation avec Compilatio réel reste attendue et l’issue #5 demeure ouverte.** Q01 a été validé en interne ; les tests automatisés utilisent une intégration antiplagiat simulée. Les rapports et scores Compilatio ne sont pas exportés.
- Répondez dans l’[issue #6](https://github.com/edunover/moodle-local_flatarchive/issues/6) ou l’[issue #5](https://github.com/edunover/moodle-local_flatarchive/issues/5), en indiquant le build **2026111106**, les versions Moodle/PHP/navigateur, les cas testés et le résultat. Le téléchargement public ne nécessite pas de compte GitHub ; les commentaires en nécessitent un. Un retour privé est également possible à [christophe@edunover.eu](mailto:christophe@edunover.eu), sans archive de cours sensible.

## Parcours de validation

Pour chaque cas, consignez : **Réussi / Échec / Non testé**, le résultat obtenu et les étapes de reproduction. « Non testé » ne constitue pas une validation.

| Cas | Manipulation | Résultat attendu |
|---|---|---|
| Installation neuve | Installez le ZIP via l’installateur Moodle sur une instance sans Flat Archive. | Installation terminée, version 0.3.0-beta.2, accès à Flat Archive et aux paramètres du plugin. |
| Mise à jour | Sur une autre instance de test ou une copie, mettez à jour depuis 0.3.0-beta.1 ou un build beta.1+fix ; conservez aussi le cas de mise à jour depuis 0.2.0 si pertinent. | Cours préservés, CSS enregistré conservé, mise à jour terminée. |
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

Ouvrez le [formulaire de retour bêta](https://github.com/edunover/moodle-local_flatarchive/issues/new?template=beta_feedback.yml) et remplissez les champs. Un compte GitHub est nécessaire. Le formulaire est aussi accessible depuis Issues → New issue → Beta feedback / Retour bêta. Le modèle ci-dessous reste disponible pour préparer votre retour.

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
