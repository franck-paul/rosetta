# Plugin Rosetta pour Dotclear 2

Gestion des traductions de billets et de pages.
Lire également l'article de blog : [Plugin-Rosetta-07-pour-Dotclear](https://open-time.net/post/2017/12/07/Plugin-Rosetta-07-pour-Dotclear).

## Principe

### Côté public

#### Gestion des URLs

- Interception de l'URL à servir si un paramètre lang=nn est présent :
  -> Si l'URL correspond à un post_id géré par Rosetta :
     -> Si une correspondance existe dans la langue demandée :
        -> redirige vers la nouvelle URL

- S'il n'y a pas de paramètre lang=nn dans l'URL, et qu'on sert un billet ou une page, on cherche une correspondance avec le accept-language du browser. Cette fonction est à activer explicitement dans les options du plugin.

- Widget avec la liste des traductions disponibles pour le billet/la page courant(e)

### Côté administration

#### Page d'édition d'un billet/d'une page

En fin de page, lister les traductions disponibles pour le billet (avec lien vers la page d'édition correspondante), possibilité de supprimer ou d'ajouter une traduction existante.

#### Page des listes des billets et des pages

Ajout de deux colonnes, langue et traductions, cette dernière donnant accès aux billets/pages attaché(e)s à celui listé.
Nota : dans les popup de sélection de billet ou de page, une seule colonne est ajoutée : langue.

## Table des correspondances (rosetta)

- src_id (integer) -> post/page id
- src_lang (string) -> src lang
- dst_id (integer) -> cf src
- dst_lang (string) -> dst lang

## Composants publics

- Widget traductions dispos (billet/page) dans le contexte du billet/de la page seul(e) :
  liste des URLs des billets (`<a href="url-billet">langue</a>`)
- Balise template équivalente (utilisable aussi dans les contextes de liste des billets) :
  {{tpl:RosettaEntryList [include_current={std|link|none}]}} (std par défaut)
- Injection meta : alternate / hreflang dans le `<head>` (contexte post/page)

## Notes

1. La gestion est pour l'instant limitée aux billets et pages. Une extension du principe aux autres contextes demanderait de revoir le schéma de la table, probablement pour basculer sur les URLs relatives au blog — ce qui implique au passage de prévoir la mise à jour en cas de changement de celles-ci - plutôt que les ID qui permettent la gestion en cascade des suppressions de billets/pages.

1. En page d'édition d'un billet (ou d'une page) : voir la possibilité de créer une nouvelle publication avec la langue pré-positionnée sur le formulaire d'édition.

1. Voir la possibilité de limiter les flux RSS (billets) à une langue donnée (widget supplémentaire).
