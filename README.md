# Plugin Rosetta pour Dotclear 2

## Principe

Interception de l'URL à servir.
Si un paramètre lang=nn est présent :
	Si l'URL est gérée :
		Si une correspondance existe dans la langue demandée :
			- rediriger vers la nouvelle URL

Table des correspondances :

- src_id		integer		post id
- src_lang		string		src lang
- dst_id		integer		cf src
- dst_lang		string		dst lang

Composants publics :

- Widget traductions dispos (billet/page) : liste des URLs des billets (<a href="url-billet">langue</a>)
- Balise template équivalente
