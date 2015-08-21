# Plugin Rosetta pour Dotclear 2

## Principe

- Interception de l'URL à servir si un paramètre lang=nn est présent :
	Si l'URL correspond à un post_id géré par Rosetta :
		Si une correspondance existe dans la langue demandée :
			- rediriger vers la nouvelle URL

- Widget avec la liste des traductions disponibles pour le billet/la page courant(e)

Table des correspondances (rosetta) :

- src_id		integer		post id
- src_lang		string		src lang
- dst_id		integer		cf src
- dst_lang		string		dst lang

Composants publics :

- Widget traductions dispos (billet/page) : liste des URLs des billets (<a href="url-billet">langue</a>)
- Balise template équivalente (utilisable dans les contextes de liste des billets) :
  {{tpl:RosettaEntryList [include_current={std|link|none}]}} (std par défaut)
- Injection meta : alternate / hreflang dans le <head> (contexte post/page)

Notes :

1. La gestion est pour l'instant limitée aux billets et pages. Une extension du principe aux autres contextes demanderait de revoir le schéma de la table, probablement pour basculer sur les URLs relatives au blog — ce qui implique au passage de prévoir la mise à jour en cas de changement de celles-ci - plutôt que les ID qui permettent la gestion en cascade des suppressions de billets/pages.

2. L'affichage d'un billet (sans lang=nn en arguments d'URL) pourrait être intercepté pour trouver s'il existe une version correspondant au accept-language du browser, si le billet demandé ne correspond pas.
À faire dans rosettaPublicBehaviors::urlHandlerGetArgsDocument().

3. Page d'édition d'un billet/d'une page:
En fin de page, lister les traductions disponibles pour le billet (avec lien vers la page d'édition correspondante), possibilité de supprimer ou d'ajouter une traduction existante, ou d'en créer une nouvelle, avec bascule sur la page d'édition d'un nouveau billet avec la langue pré-positionnée.

4. Voir la possibilité de limiter les flux RSS (billets) à une langue donnée (widget supplémentaire).
