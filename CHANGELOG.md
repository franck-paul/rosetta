# Changelog

## 10.0 — 2026-11-08

- Use getter methods rather than widget properties as far as possible
- Ensure $params exists and is an array
- Cope with RTL
- Use typed getter/setter Cursor method, DC 2.40+
- Enforces some verification before registering module

## 9.0 — 2026-08-03

- To soon for that!
- Use App::url()-&gt;isType() where it is relevant
- Avoid using magic _get(), _set() methods of MetaRecord (2.39+)
- Avoid using magic methods for user preferences and blog settings (2.39+)
- Forgot these ones
- Use MetaRecord::strField(), MetaRecord::intField() and MetaRecord::boolField() whenever is possible Fav Icon

## 8.9 — 2026-04-20

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 8.8 — 2026-03-20

- DA -&gt; DC

## 8.7 — 2025-10-20

- Simplify construction of langs combo

## 8.6 — 2025-10-11

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 8.5 — 2025-09-30

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 8.4 — 2025-09-22

- Use backend services rather than static calls

## 8.3 — 2025-09-13

- Cope with 3rd parameter of admin???ListHeader/admin???ListValue behaviors

## 8.2 — 2025-09-12

- Replace static call to Ctx by context() — thanks JcDenis for advice

## 8.1.3 — 2025-09-12

- Cope with status for Popup in ManagePopup only

## 8.1.2 — 2025-09-11

- Do not populate additional columns for posts/pages if they not present in main list

## 8.1.1 — 2025-09-09

- Fix context checking for popup

## 8.1 — 2025-09-07

- DC 2.36 compliance

## 8.0 — 2025-09-06

- DC 2.36 compliance

## 7.0 — 2025-08-15

- Remove top-add class for buttons container, 2.35+
- PoC : Better this way
- PoC : using Row with typed properties to map Rosetta table record

## 6.12 — 2025-07-21

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 6.11 — 2025-06-01

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 6.10.1 — 2025-05-31

- Fix creation and edit button behavior

## 6.10 — 2025-05-30

- Add context selector to static home widget

## 6.9.1 — 2025-05-30

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 6.9 — 2025-05-30

- Post type is not URL type, thanks JcDenis ;-)

## 6.8 — 2025-05-30

- Add a new widget for blog language (useful only with static home with an translated entry)

## 6.7 — 2025-05-30

- Cope with language specified in URL to set current item in widget list

## 6.6 — 2025-05-29

- Cope with lang parameter for static home
- Cope with static home in widget (URL handler not yet developed)

## 6.5 — 2025-05-29

- Replace single entry used as static home to the translated one if exists

## 6.4 — 2025-05-29

- Cope with single entry set as static home
- Use integer values as they are rather than using string

## 6.3.3 — 2025-05-25

- Cope with post_type missing

## 6.3.2 — 2025-05-25

- Same player shoot again

## 6.3.1 — 2025-05-25

- Fix type of given arg
- Use also new-stuff class as top-add might be hidden by ads-blockers

## 6.3 — 2025-05-12

- Use dotclear.confirm rather than window.confirm

## 6.2 — 2025-05-05

- Use Strong(…) rather than Text('strong', …)

## 6.1 — 2025-05-04

- Use Helper\Html\Form\Span rather than Text('span',…)

## 6.0.1 — 2025-05-03

- Uninstall table name must no include db prefix
- Add translate="no" to several components

## 6.0 — 2025-03-29

- Use TemplateHelper for template blocks/values DC 2.34+
- Add translate="no" when display language codes

## 5.3 — 2025-02-25

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 5.2 — 2025-02-21

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 5.1 — 2025-02-21

- Switch from legacy form:: to Help/Html/Form/...

## 5.0 — 2025-01-09

- 2.33 compliance

## 4.3 — 2024-08-23

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 4.2.1 — 2024-01-12

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 4.2 — 2023-11-11

- Use SqlStatement

## 4.1 — 2023-10-27

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 4.0 — 2023-10-25

- Use instanced item constants rather than interface ones
- Avoid using magic methods as far as possible
- Avoid magic method as far as possible
- Use 2.28 Config methods
- 2.28 compliance
- Use int for post id everywhere
- Fix type hint

## 3.0 — 2023-08-26

- Dotclear 2.27 compliance, PHP 8.1+
- 2.27 compliance (PHP 8.1+)

## 2.7.2 — 2023-08-17

- Fix #18

## 2.7.1 — 2023-07-03

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 2.7 — 2023-07-02

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 2.5 — 2023-07-02

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 2.4 — 2023-06-30

- Use only POST method and use Json format for REST requests
- Use dotclear.servicesGet() and dotclear.servicesPost() rather than $.get() and $.post
- Switch get requests to post for db modification

## 2.3.1 — 2023-06-27

- Add local static $init variable

## 2.3 — 2023-06-20

- Use dcCore::app()-&gt;adminurl-&gt;redirect() rather than Http::redirect()

## 2.2 — 2023-06-20

- Use admin URLs

## 2.1 — 2023-06-20

- Use admin URLs

## 2.0 — 2023-06-14

- Switch to namespace
- Prepare 2.26
- Minified versions

## 1.1 — 2022-12-18

- 2.24 compliance

## 1.0 — 2022-11-30

- Dotclear 2.24 compliance

## 0.10.1 — 2022-08-14

- Usual oups :-)

## 0.10 — 2022-08-11

- Update default branch in _define.php for alternative repository
- Add alternative repository

## 0.9 — 2021-08-07

- Dotclear 2.19 compliance
- Avoid calling obsolete function

## 0.8 — 2020-03-12

- No more inline js
- Remove type="text/javascript" from script element
- Adapt widget code, add support and details URLs
- jQuery 3+ compliance
- Use let and const rather than var (ES2015/ES6), rewrite promise
- short notation for array (array() → [])
- Refine error messages and settings tab is only available for blog admin or super-admin

## 0.7 — 2017-12-07

- Better this, need to not cast string to integer else it will not work very well dude!
- statically called function cannot use $this
- Add import/export capabilities
- No more CDATA in JS

## 0.6 — 2016-08-16

- dc 2.10 min, next release will be 0.6
- Optional display of columns in posts and pages lists (DC 2.10 min)
- Take care of activation before coping with additionnal columns (posts and pages lists)
- Local getAcceptLanguages() function not more needed as CB has been fixed with 2.9

## 0.5 — 2016-03-07

- Switch of translated entries only if required (home, …)
- Duplicate and adapted code not more needed with Dotclear 2.9 too
- Local adapted copy of dcPager is not more needed with Dotclear 2.9
- Add dependency to Dotclear 2.9
- Cope with the new behaviour used to swap translated posts/pages in getPosts rs
- Using dcPage::cssLoad, dcPage::jsLoad and dcPage::getPF is a better way to load plugin resources
- Cope with CB update for getAcceptLanguages()
- Check findAllTranslations() return: may be false (aka not an array).
- Autres correctifs et améliorations.

## 0.4.1 — 2015-09-21

- Check findAllTranslations() return: may be false (aka not an array).
- Avoid possible redirect loop

## 0.4.0 — 2015-09-06

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 0.4 — 2015-09-06

- New 0.4 branch
- Prepare 0.4
- Removing empty select in language combo
- Implements futures behaviors for column's management of post/page list (std and mini)
- Lowercase languages returned
- Get real list of accepted-languages
- Time to developp the next release

## 0.3.0 — 2015-09-02

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 0.3 — 2015-09-02

- Changelog
- Fix page attachment, closes #1
- Add an option to activate the browser's accept-language redirect mechanism
- Cope with browser accept-language for post/page URL without lang=nn, should be an option (to be developped)
- Open popup (list of posts/pages) with the according post-type, fixes #2

## 0.2.0 — 2015-08-29

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 0.2 — 2015-08-29

- Time to develop the next release (0.2)
- Update changelog in README.md

## 0.1.0 — 2015-08-27

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 0.1 — 2015-08-27

- Update README.me
- Admin part, entry edition: Ajax way choosen, more to come…
- Admin part, entry edition: add rosetta management (actions not yet developped).
- Admin part, first step
- A little more doc in README.md
- Add alternate+hreflang links in &lt;head&gt; for current entry
- Add &lt;strong&gt;…&lt;/strong&gt; for current language
- Add {{tpl:RosettaEntryList}} template tag
- Autres correctifs et améliorations.
