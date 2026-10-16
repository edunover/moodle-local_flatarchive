# Sécurité / Security

[Français](#français) · [English](#english)

## Français

> **Bêta — essais uniquement.** Logiciel fourni en l’état, sans garantie, dans les limites du droit applicable. Utilisez une instance de test et des données fictives ou anonymisées ; effectuez une sauvegarde avant installation et vérifiez les archives produites. Les limitations de responsabilité ne suppriment pas les droits impératifs. [Avertissement complet](docs/BETA_WARNING.md#français).

Ce plugin en développement crée des **copies consultables de données de cours potentiellement sensibles**. Une revue du code et des tests réussis réduisent les risques connus ; ils ne garantissent pas une protection contre toutes les attaques et ne constituent pas un test d’intrusion indépendant.

### Autorisations et requêtes

- Une connexion et la capacité `local/flatarchive:archive` dans le contexte du cours sont requises. Rôles autorisés par défaut : gestionnaire (`manager`) et enseignant éditeur (`editingteacher`).
- La génération exige des données POST et une clé de session Moodle `sesskey` valide. Le service AJAX utilise la validation de l’API externe Moodle.
- Chaque module demandé doit appartenir au cours autorisé, être pris en charge, être accessible à l’utilisateur et satisfaire les capacités natives du module. Le serveur répète les contrôles indépendamment de l’interface de sélection.
- Les rapports individuels sont filtrés selon les capacités natives utilisées par l’exporteur, les groupes, l’anonymat et les règles de relecture. Toute modification de ces contrôles nécessite des tests de non-régression.
- Les jetons de téléchargement contiennent 192 bits aléatoires, sont liés à la session et à l’utilisateur et expirent après une heure. Le téléchargement vérifie à nouveau l’accès au cours et le droit d’archivage. Aucun chemin de fichier fourni par le client n’est utilisé. Les liens symboliques vers le ZIP et les chemins non canoniques du dossier de travail sont rejetés.

### Instantanés des rapports du cours

Les instantanés du carnet de notes, de l’achèvement et de la participation font chacun l’objet de contrôles de capacités natives. Ils comprennent les participants dont l’inscription est active dans le groupe courant autorisé et peuvent couvrir des activités non sélectionnées pour l’export des contenus. Sans droit de consultation des notes cachées, les valeurs cachées et les totaux agrégés sont omis. La participation exclut les données brutes des journaux et les adresses IP ; les événements anonymes nécessitent le droit natif correspondant. Toutes les données des rapports sont intégrées au ZIP téléchargé et relèvent des mêmes limites de confidentialité et de conservation que les rapports d’activité exportés.

### HTML généré

La mise en forme et le nettoyage des textes par Moodle, ainsi que l’échappement des libellés, constituent la première protection du contenu. Le traitement final des documents générés ajoute une politique de sécurité du contenu (CSP) avant le contenu de la page : scripts, objets, cadres intégrés, connexions réseau par script, redéfinition de l’URL de base et soumission de formulaires sont interdits. Il supprime les scripts, gestionnaires d’événements, métadonnées de rafraîchissement et formulaires actifs ; les cadres externes deviennent des liens ordinaires. Cette politique nécessite un navigateur qui respecte la CSP définie dans les métadonnées HTML. Le HTML source reste modifiable par toute personne possédant le ZIP ; « statique » ne signifie ni inviolable ni signé cryptographiquement.

Les images et les médias audio/vidéo peuvent toujours être chargés par HTTP(S) afin de préserver les contenus pédagogiques. Ces chargements peuvent communiquer l’adresse IP du lecteur à des fournisseurs distants. Les destinations externes ne sont ni téléchargées ni contrôlées pour vérifier leur sécurité. Les fichiers HTML/SVG originaux ouverts séparément ne sont **pas** couverts par la CSP des pages générées. N’hébergez pas les pièces jointes extraites sous la même origine web que le site Moodle authentifié.

### Pièces jointes et H5P

Les pièces jointes originales sont copiées à l’identique, octet par octet. Le plugin ne recherche pas les logiciels malveillants, ne supprime pas les macros Office, ne désinfecte pas les paquets H5P sources et ne garantit pas la sécurité des logiciels tiers de consultation des documents. Utilisez les contrôles institutionnels de dépôt, d’antivirus et de traitement des documents. Un export réussi ne constitue pas une analyse antivirus.

La conversion H5P n’exécute pas le JavaScript du paquet. Elle rejette les chemins dangereux ou dupliqués et les liens symboliques ZIP, limite le nombre d’entrées à 20 000 et la taille décompressée déclarée à 2 Gio, limite chaque document JSON à 16 Mio et la profondeur d’imbrication à 128, et borne la lecture des ressources à leur taille déclarée plus un octet. Seules les extensions de médias passifs explicitement autorisées sont extraites dans les dossiers de ressources consultables. Les fichiers `.h5p` originaux restent disponibles comme pièces jointes téléchargeables.

### Limites d’exploitation

- Maintenez Moodle, PHP, la base de données, les modules natifs et les bibliothèques du serveur à jour. Ce dépôt n’embarque pas et n’audite pas l’ensemble de leur code source.
- La génération d’archives est synchrone. Des cours très volumineux, des contenus particulièrement complexes ou des requêtes autorisées répétées peuvent épuiser le temps d’exécution ou la mémoire PHP, ou l’espace disque. Cette version ne comprend ni quota global de taille d’archive ni limitation de fréquence par utilisateur. Appliquez des limites de ressources sur le serveur et surveillez l’espace disque.
- Le répertoire temporaire Moodle doit rester privé. L’expiration d’un jeton ne constitue pas un effacement sécurisé. Les copies temporaires et les sauvegardes du serveur peuvent subsister au-delà de l’heure de disponibilité du téléchargement.
- Cette version du plugin n’implémente pas de fournisseur dédié pour l’API Moodle de protection des données personnelles (Privacy API). Ses fichiers temporaires peuvent contenir des données personnelles ; le déploiement doit prendre en compte leur stockage temporaire et leur durée de conservation. Aucune certification réglementaire ni couverture complète de la Privacy API n’est revendiquée.
- Les ZIP ne comportent ni contrôle d’accès, ni chiffrement, ni mécanisme de révocation. N’accordez les droits de téléchargement qu’à des membres du personnel de confiance. Restreignez le stockage et la redistribution des données étudiantes.
- Un serveur compromis, un plugin PHP installé malveillant, un utilisateur privilégié du système de fichiers, un navigateur vulnérable ou un logiciel vulnérable de consultation des pièces jointes sortent du périmètre des protections fournies ici.

### Signaler une vulnérabilité

Signalez toute vulnérabilité suspectée en privé à [christophe@edunover.eu](mailto:christophe@edunover.eu). Ne publiez pas dans une issue publique de données étudiantes, de jetons, de clés de session ou de détails permettant d’exploiter une vulnérabilité non corrigée. Indiquez les versions du build, de Moodle et de PHP, une reproduction minimale avec des données fictives, les droits d’accès attendus et le comportement observé.

Références : [politique de sécurité Moodle](https://moodledev.io/general/development/policies/security), [CSRF](https://moodledev.io/general/development/policies/security/crosssite-request-forgery), [accès non autorisé](https://moodledev.io/general/development/policies/security/unauthorised-access), [CSP](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy).

## English

> **Beta — testing only.** Provided as is, without warranty to the extent permitted by applicable law. Use a test instance and synthetic or anonymised data; back up before installation and verify generated archives. Liability limitations do not exclude mandatory legal rights. [Full warning](docs/BETA_WARNING.md#english).

This development plugin creates **readable copies of potentially sensitive course data**. A review and passing tests reduce known risks; they do not establish immunity from attacks or constitute an independent penetration test.

### Authorisation and requests

- Login and `local/flatarchive:archive` are required in the course context. Default roles: manager and editingteacher.
- Generation requires POST data and a valid Moodle `sesskey`. The AJAX service uses Moodle external API validation.
- Every requested module must belong to the authorised course, be supported, be user-visible and satisfy native module capabilities. The server repeats checks independently of the selection interface.
- Individual reports are filtered by the exporter's native capabilities, groups, anonymity and review rules. Changes to those checks require regression tests.
- Download tokens contain 192 random bits, are session/user-bound and expire after one hour. Download rechecks course access and archive permission. No client path is used. ZIP symlinks and noncanonical workspace paths are rejected.

### Course report snapshots

Gradebook, completion and participation snapshots have independent native capability checks. They include active enrolled participants in the permitted current course group and can cover activities not selected for content export. Without hidden-grade permission, hidden values and aggregate totals are omitted. Participation excludes raw log payloads and IP addresses; anonymous events require the native permission. All report data becomes part of the downloaded ZIP and has the same confidentiality/retention boundaries as exported activity reports.

### Generated HTML

Moodle text formatting/cleaning and escaped labels provide the primary content boundary. The final generated document pass adds CSP before page content: scripts, objects, frames, network connections from script, base URL overrides and form submissions are prohibited. It removes scripts, handlers, refresh metadata and active forms; external iframes become ordinary links. The policy requires a browser that honours CSP in HTML metadata. Source HTML remains editable by anyone holding the ZIP; “static” is not tamper-proof or cryptographically signed.

Images and audio/video may still load over HTTP(S) to preserve teaching content. Such loads can disclose the reader's IP address to remote providers. External destinations are not downloaded or checked for safety. Original HTML/SVG files opened separately are **not** covered by generated-page CSP. Do not host extracted attachments under the authenticated Moodle origin.

### Attachments and H5P

Original attachments are copied byte-for-byte. The plugin does not scan malware, remove Office macros, sanitise source H5P packages or guarantee safe third-party document viewers. Use the institution's upload/antivirus and document-handling controls. Do not treat a successful export as a malware scan.

H5P conversion does not execute package JavaScript. It rejects unsafe/duplicate paths and ZIP symlinks, caps entries at 20,000 and declared uncompressed size at 2 GiB, caps each JSON document at 16 MiB and nesting at 128, and bounds asset reads to the declared size plus one byte. Only listed passive media extensions are unpacked into browsable asset folders. Original `.h5p` files remain downloadable attachments.

### Operational boundaries

- Keep Moodle, PHP, database, native modules and server libraries patched. This repository does not vendor or audit their complete source trees.
- Archive generation is synchronous. Very large courses, unusually complex content or repeated authorised requests can exhaust PHP time/memory or disk space. There is no global archive size quota or per-user rate limiter in this release. Apply server resource limits and monitor disk usage.
- Moodle's temp directory must be private. Token expiry is not secure erasure. Temporary copies and server backups may outlive the one-hour download window.
- The plugin does not implement a dedicated Moodle Privacy API provider in this release. Its temporary files can contain personal data; deployment must account for temporary storage and retention. No claim of regulatory certification or complete Privacy API coverage is made.
- ZIPs contain no access-control layer, encryption or revocation. Download privileges should be granted only to trusted staff. Restrict storage and redistribution of student records.
- A compromised server, malicious installed PHP plugin, privileged filesystem user, vulnerable browser or attachment viewer is outside the protections supplied here.

### Reporting

Report a suspected vulnerability privately to [christophe@edunover.eu](mailto:christophe@edunover.eu). Do not put student records, tokens, session keys or exploit details for an unpatched vulnerability in a public issue. Include build/Moodle/PHP versions, minimal synthetic reproduction, expected access rights and observed behaviour.

Reference guidance: [Moodle security policy](https://moodledev.io/general/development/policies/security), [CSRF](https://moodledev.io/general/development/policies/security/crosssite-request-forgery), [unauthorised access](https://moodledev.io/general/development/policies/security/unauthorised-access), [CSP](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy).
