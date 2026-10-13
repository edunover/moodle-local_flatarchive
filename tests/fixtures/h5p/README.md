# Validation manuelle H5P natif

Utiliser des activités **H5P natives** (`mod_h5pactivity`), pas `mod_hvp`.
Le paquet d'installation à tester est `local_flatarchive-2026101302.zip`.

## Contenus à préparer

1. **Drag the Words** : plusieurs mots, indice, feedback correct/incorrect,
   distracteur, image locale. Vérifier le texte à trous et le texte corrigé.
2. **Drag and Drop** : image de fond, deux zones, texte et image à déplacer,
   un élément valable dans plusieurs zones. Vérifier la numérotation et les
   associations dans le corrigé ; aucun glisser-déposer ne doit être actif.
3. **Question Set** : choix multiple, vrai/faux, texte à trous, questions tirées
   aléatoirement. Vérifier que toutes les questions du paquet sont archivées,
   avec leurs corrigés et les paramètres du tirage. Ajouter un type non reconnu
   pour vérifier l'avertissement explicite.
4. **Course Presentation** : trois diapositives, texte, image, tableau, lien web,
   question intégrée, commentaires et lien vers une diapositive précise.
   Vérifier le sommaire, précédent/suivant et les liens entre diapositives.
   Le rendu est volontairement linéarisé pour la lecture, sans animations.
5. **Interactive Video** : fichier vidéo local, sous-titres VTT, deux interactions
   à des moments différents et un saut conditionnel. Vérifier le lecteur natif,
   les fichiers de sous-titres, la chronologie, les corrigés et les sauts décrits.
   Une vidéo distante doit être signalée comme dépendante d'Internet.

## Tentatives et droits

- Faire enregistrer des tentatives par deux participants, dont une inachevée.
- Exporter avec un enseignant autorisé à consulter les rapports : vérifier auteurs,
  dates, réponses, scores enregistrés et états d'achèvement/réussite.
- Utiliser deux groupes séparés et un enseignant sans accès à tous les groupes :
  les rapports du groupe non accessible doivent être absents.
- Désactiver le suivi ou retirer le droit de consulter les tentatives : aucune
  réponse privée ne doit être incluse.
- Un étudiant sans droit de gestion des activités ne doit pas pouvoir exporter
  le paquet et les solutions.

## Archive téléchargée

Décompresser le ZIP, couper le réseau puis ouvrir `index.html`.

- La racine contient uniquement `index.html` et `resources/`.
- Les liens locaux, les retours à l'index et les pages Paramètres fonctionnent.
- Les médias locaux s'affichent ; les références non embarquées sont signalées.
- Le fichier `.h5p` original est téléchargeable depuis la page de l'activité.
- Aucune réponse nouvelle, correction interactive ou commande d'édition.
- Pas de marqueur de langue `[[...]]`, de fichier manquant non signalé ou de page vide.

Les tests automatiques utilisent des paquets synthétiques construits dans PHPUnit
et les rapports du générateur Moodle. Cette validation réelle complète leur couverture
avec les paquets produits par les bibliothèques installées sur la sandbox.
