# Plugin Rosetta pour Dotclear 2

## Principe

Interception de l'URL à servir.
Si un paramètre lang=nn est présent :
	Si l'URL est gérée :
		Si une correspondance existe dans la langue demandée :
			- servir la nouvelle URL (avec le paramètre lang=nn ?)

Table des correspondances :

- src_id		integer		post id
- src_lang		string		src lang
- dst_id		integer		cf src
- dst_lang		string		dst lang

## ToDo

1. Gestion des correspondances :

1.1 Billets/Pages

2. Gestion de l'interface

2.1 Bascule à la volée de la langue publique du blog ?
2.2 Impact sur le cache des templates
2.3 Impact sur le cache statique
2.4 SimpleMenu ?

3. Navigation

3.1 Widget liste des langues
3.2 Widget traduction (billet/page), cf 1.1
3.3 Restriction à une langue seulement

4. Contenu annexe

4.1 Flux RSS/Atom des billets en une seule langue
