# Static mathematics / Mathématiques statiques

[Français](#français) · [English](#english)

## Français

Depuis `0.3.0-beta.1+fix.6`, les formules courantes sont converties **au moment de l’export** en MathML natif dans les pages HTML générées. La lecture ne nécessite ni Moodle, ni MathJax, ni JavaScript, ni connexion Internet, ni installation TeX sur le serveur. Il ne s’agit pas d’un moteur LaTeX complet ni d’une conversion en image.

Le navigateur doit prendre en charge MathML natif. La typographie peut varier selon le navigateur et les polices installées. Le standard décrit la présentation mathématique ; il ne calcule pas les expressions et ne permet pas de rejouer une activité.

### Notations prises en charge

- Délimiteurs : `\(...\)` en ligne ; `\[...\]`, `$$...$$` et `[tex]...[/tex]` en bloc. Un dollar simple reste du texte, notamment pour les prix.
- Lettres, entiers, opérateurs arithmétiques, parenthèses, groupes `{...}`, indices `_` et exposants `^`. Comme en TeX, écrire `x^{12}` pour un exposant à plusieurs caractères ; `x^12` signifie `x¹2`.
- Fractions `\frac`, `\dfrac`, `\tfrac` ; racines `\sqrt`, avec indice facultatif, par exemple `\sqrt[3]{x}`.
- Lettres grecques usuelles ; opérateurs tels que `\times`, `\cdot`, `\leq`, `\neq`, `\in`, `\subseteq`, `\rightarrow`, `\infty`, `\partial` ; sommes, produits et intégrales.
- Fonctions usuelles (`\sin`, `\cos`, `\log`, `\ln`, `\exp`, `\lim`, etc.), texte simple `\text{...}`, opérateur nommé `\operatorname{...}` et caractères droits `\mathrm{...}`.
- Délimiteurs extensibles `\left...\right`, accents `\hat`, `\bar`, `\vec`, `\dot`, `\ddot`, espacements usuels et caractères spéciaux échappés.

Les listes exactes de commandes sont définies dans `classes/archive/math_parser.php`.

### Conservation et limites

La source de chaque formule convertie est conservée dans une annotation MathML `application/x-tex` et dans l’attribut `title`, visible au survol dans les navigateurs qui l’affichent. Une formule inconnue, mal formée ou trop grande reste **entièrement en LaTeX**, avec un avertissement visible. Aucun rendu partiel n’est substitué à la formule.

Les macros personnalisées, packages, environnements (matrices, alignements, chimie, TikZ…), commandes HTML/liens, polices mathématiques spéciales et configurations MathJax propres au site ne sont pas interprétés. Limites par formule : 4 096 octets, 1 024 tokens et 32 niveaux de traitement imbriqués.

Le traitement porte sur les nœuds de texte du corps des pages générées, après leur nettoyage. Une formule doit être contenue dans un seul nœud de texte : les délimiteurs incomplets ou séparés par des balises HTML sont conservés tels quels. Les exemples dans `pre`, `code`, `kbd`, `samp`, les contrôles, SVG, MathML existant et les classes `tex2jax_ignore` / `mathjax_ignore` sont exclus. Les attributs HTML et les pièces jointes originales ne sont pas transformés. Les anciens éléments `<tex>` ne sont pas pris en charge.

Les filtres Moodle ne sont pas réactivés ; les marqueurs `{mlang}` / `{multilang xx}` sont préservés. Aucun programme système, fichier externe ou service de rendu n’est exécuté. Les commandes non reconnues ne peuvent pas déclencher de lecture de fichier, de macro ou de script.

### Vérification manuelle

Exporter M01, ainsi qu’une étiquette et une description contenant `\(a^2+b^2=c^2\)` et `$$\frac{-b\pm\sqrt{b^2-4ac}}{2a}$$`. Décompresser, ouvrir `index.html` puis les pages concernées hors ligne et vérifier les exposants, la fraction et la racine. Vérifier aussi `\(\commandeinconnue{x}\)` : la source complète et l’avertissement doivent apparaître, sans interrompre l’archive.

Les tests automatisés vérifient la structure mathématique, les limites, les chaînes malveillantes, les exclusions, l’idempotence, la conservation du contenu Moodle et les pages après extraction réelle d’un ZIP. Ils ne constituent pas une comparaison visuelle exhaustive des navigateurs ni une garantie de compatibilité avec toutes les expressions LaTeX.

## English

Since `0.3.0-beta.1+fix.6`, common formulas are converted **during export** to native MathML in generated HTML pages. Reading needs no Moodle, MathJax, JavaScript, network connection or server TeX installation. This is a bounded TeX subset, not a complete LaTeX engine or an image conversion.

Use a browser supporting native MathML; typography can vary with the browser and installed fonts. MathML describes mathematical presentation; it neither calculates expressions nor replays activities.

Supported delimiters are `\(...\)` inline and `\[...\]`, `$$...$$`, `[tex]...[/tex]` in display mode. Single dollars remain literal. Supported notation includes groups, subscripts/superscripts, fractions, roots, common Greek letters/operators/functions, sums/products/integrals, scalable delimiters, basic accents/spacing, plain `\text`, `\operatorname` and `\mathrm`. The exact command lists are in `classes/archive/math_parser.php`. TeX's single-token argument rule applies: use `x^{12}`, not `x^12`, for a two-digit exponent.

Every converted formula retains its source in an `application/x-tex` MathML annotation and a `title` attribute. Unknown, invalid or oversized formulas retain their **entire original source with a visible notice**, never a partial rendering. Limits per formula: 4,096 bytes, 1,024 tokens and 32 nested processing levels.

Custom macros, packages, environments (matrices, alignment, chemistry, TikZ…), HTML/link commands, special mathematical fonts and site-specific MathJax configuration are unsupported. Formulas must occupy a single text node. Incomplete delimiters or formulas split by HTML markup remain untouched. Code/examples, controls, existing SVG/MathML, `tex2jax_ignore` / `mathjax_ignore`, attributes and original attachments are excluded. Legacy `<tex>` elements are unsupported.

Moodle content filters remain disabled and multilingual source markers are preserved. No subprocess, external file, rendering service or arbitrary macro is invoked. Tests cover mathematical structure, malicious/invalid/oversized input, exclusions, idempotence, unchanged Moodle records, and offline files after actual ZIP extraction. Manual browser validation remains necessary; tests do not establish complete LaTeX coverage.

For manual validation, export M01 plus a label and activity description containing `\(a^2+b^2=c^2\)` and `$$\frac{-b\pm\sqrt{b^2-4ac}}{2a}$$`. Extract the ZIP and inspect these pages offline. An unknown command such as `\(\unknown{x}\)` must retain the complete source and show a notice without aborting the archive.

## References / Références

- [W3C MathML Core](https://www.w3.org/TR/mathml-core/) — static mathematical markup and TeX source annotations.
- [MDN: MathML browser support](https://developer.mozilla.org/en-US/docs/Web/MathML) — native support in current browser families.
