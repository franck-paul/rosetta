# Plugin Rosetta pour Dotclear 2

Gestion des traductions de billets et de pages.

## Principe

### Côté public :

#### Gestion des URLs ;

Interception de l'URL à servir si un paramètre lang=nn est présent :
	Si l'URL correspond à un billet ou une page géré par Rosetta :
		Si une correspondance existe dans la langue demandée :
			-> redirige vers la nouvelle URL

S'il n'y a pas de paramètre lang=nn dans l'URL, et qu'on sert un billet ou une page, on cherche une correspondance avec le accept-language du browser. Cette fonction est à activer explicitement dans les options du plugin.

#### Éléments de thème :

Widget avec la liste des traductions disponibles pour le billet/la page courant(e)

Balise template pour afficher la liste des traductions des billets listés

Injection des meta alternate/hreflang dans le <head> des traductions existantes pour le billet ou la page affichée

### Côté administration :

Page d'édition d'un billet/d'une page :
En fin de page, lister les traductions disponibles pour le billet (avec lien vers la page d'édition correspondante), possibilité de supprimer ou d'ajouter une traduction existante.

Page des listes des billets et des pages :
Ajout de deux colonnes, langue et traductions, cette dernière donnant accès aux billets/pages attaché(e)s à celui listé.
Nota : dans les popup de sélection de billet ou de page, une seule colonne est ajoutée : langue.

## Table des correspondances (rosetta)

- src_id		integer		post id
- src_lang		string		src lang
- dst_id		integer		cf src
- dst_lang		string		dst lang

## Composants publics

Widget traductions dispos (billet/page) dans le contexte du billet/de la page seul(e) :
  liste des URLs des billets (<a href="url-billet">langue</a>)

Balise template équivalente (utilisable aussi dans les contextes de liste des billets) :
  {{tpl:RosettaEntryList [include_current={std|link|none}]}} (std par défaut)

## Changelog

0.1.0 - 2015/08/27
	- Public widget
	- Public template tag
	- Single entry management (page/post)

0.2.0 - 2015/08/29
	- Ajout de deux colonnes (langue et traductions) dans les listes des billets et des pages
	- Ajout d'une colonne langue dans les listes de sélection (popup) de billet ou de page

0.3.0 - 2015/09/02
	- Prise en compte du accept-anguage du browser si lang=nn n'est pas précisé pour un billet ou une page
	- Ajout d'une option pour activer la prise en charge du accept-language
	- Correction pour la gestion des pages (admin et public)

0.4.0 - 2015/09/08
	- Correction pour les langues retournées par le accept-language (Clearbricks is buggy about it)
	- Ajout d'une fonction de création d'une traduction pour l'entrée en cours d'édition
	- Ajout d'une fonction de création et d'édition d'une traduction pour l'entrée en cours d'édition
