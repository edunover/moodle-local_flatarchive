# Flat Archive 0.3.0-beta.1

Build **2026101600** · Moodle **4.4+** · Maturity **BETA / BÊTA**

## Français

Première bêta destinée à une campagne de tests sur des instances Moodle de test. Elle reprend les fonctionnalités validées dans la version alpha : export HTML des activités prises en charge, paramètres du cours/des sections/des activités, participants, notes, rapports, styles CSS institutionnels et téléchargements répétés pendant une heure dans la session propriétaire.

Cette livraison formalise la maturité Bêta et ajoute les guides de test FR/EN et un formulaire de retour GitHub. Elle ne modifie pas le format des données du plugin. Une installation neuve et une mise à jour sur chaque environnement de test restent à valider par les testeurs ; les tests automatisés ne remplacent pas ces essais.

Installez **local_flatarchive-0.3.0-beta.1.zip**, pas les archives automatiques « Source code ». Consultez [le guide français](BETA_TESTING.fr.md), [le README](../README.fr.md) et [SECURITY.md](../SECURITY.md). Le pied de page des nouvelles archives affiche la maturité Bêta ; les anciennes archives restent inchangées.

## English

First beta for testing on non-production Moodle instances. It includes the capabilities validated during alpha: static HTML export of supported activities, course/section/activity settings, participants, grades, reports, institutional CSS and repeated downloads for one hour in the owning session.

This release declares Beta maturity and adds FR/EN test guides and a GitHub feedback form. It introduces no plugin data-format migration. Testers still need to validate fresh installation and upgrade in their environments; automated tests do not replace those checks.

Install **local_flatarchive-0.3.0-beta.1.zip**, not the automatic “Source code” archives. Read the [English guide](BETA_TESTING.en.md), [README](../README.md) and [SECURITY.md](../SECURITY.md). New archive footers display Beta maturity; old archives remain unchanged.

## Limites / Limitations

- IMS/SCORM and `mod_hvp` excluded / exclus.
- Native H5P limited to the documented types / H5P natif limité aux types documentés.
- Synchronous generation; large-course limits depend on the server / Génération synchrone, limites des gros cours liées au serveur.
- Third-party course-format settings excluded / Réglages des formats de cours tiers exclus.
- External resources may require Internet; original attachments are not disinfected / Ressources externes parfois dépendantes d’Internet ; pièces jointes originales non désinfectées.
- Static reading archive, not a Moodle restore format / Archive de consultation, pas un format de restauration Moodle.
