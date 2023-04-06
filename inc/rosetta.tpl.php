<?php
/**
 * @brief rosetta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */

use Dotclear\Helper\Html\Html;

class rosettaTpl
{
    public static function EntryListHelper($post_id, $post_lang, $post_type, $include, &$current, $code_only = false)
    {
        // Get associated entries
        $ids = rosettaData::findAllTranslations($post_id, $post_lang, ($include != 'none'));
        if (!is_array($ids)) {
            return false;
        }
        // source = $ids : array ('lang' => 'entry-id')
        // destination = $table : array ('language' (or 'lang' if $code=true) => 'entry-url')
        // $current = current language
        $table   = [];
        $langs   = l10n::getLanguagesName();
        $current = '';
        foreach ($ids as $lang => $id) {
            $name = $langs[$lang] ?? $langs[dcCore::app()->blog->settings->system->lang];
            if ($post_id == $id) {
                $current = ($code_only ? $lang : $name);
            }
            if ($post_id == $id && $include != 'link') {
                $table[($code_only ? $lang : $name)] = '';
            } else {
                // Get post/page URL
                $params = new ArrayObject([
                    'post_id'    => $id,
                    'post_type'  => $post_type,
                    'no_content' => true, ]);
                dcCore::app()->callBehavior('publicPostBeforeGetPosts', $params, null);
                $rs = dcCore::app()->blog->getPosts($params);
                if ($rs->count()) {
                    $rs->fetch();
                    $url = dcCore::app()->blog->url . dcCore::app()->getPostPublicURL($post_type, Html::sanitizeURL($rs->post_url));
                    if (dcCore::app()->blog->settings->rosetta->accept_language) {
                        // Add lang parameter to the URL to prevent accept-language auto redirect
                        $url .= (strpos($url, '?') === false ? '?' : '&') . 'lang=' . $lang;
                    }
                    $table[($code_only ? $lang : $name)] = $url;
                }
            }
        }
        if (!count($table)) {
            return false;
        }
        dcUtils::lexicalKeySort($table, dcUtils::PUBLIC_LOCALE);

        return $table;
    }

    public static function rosettaEntryList($attr)
    {
        if (!dcCore::app()->blog->settings->rosetta->active) {
            return;
        }

        $option = !empty($attr['include_current']) ? (string) $attr['include_current'] : 'std';

        if (!preg_match('#^(std|link|none)$#', $option)) {
            $option = 'std';
        }

        $res = <<<EOT
                  \$rosetta_table = rosettaTpl::EntryListHelper(
                    dcCore::app()->ctx->posts->post_id,dcCore::app()->ctx->posts->post_lang,dcCore::app()->ctx->posts->post_type,
                    '$option',\$rosetta_current);
                  if (is_array(\$rosetta_table) && count(\$rosetta_table)) {
                    echo '<ul class="rosetta-entries-list">'."\n";
                    foreach (\$rosetta_table as \$rosetta_name => \$rosetta_url) {
                      \$rosetta_link = (\$rosetta_name != \$rosetta_current || '$option' == 'link');
                      \$rosetta_class = (\$rosetta_name == \$rosetta_current ? 'class="current"' : '');
                      echo '<li'.\$rosetta_class.'>'.
                        (\$rosetta_link ? '<a href="'.\$rosetta_url.'">' : '').
                        (\$rosetta_class ? '<strong>' : '').\\Dotclear\\Helper\\Html\\Html::escapeHTML(\$rosetta_name).(\$rosetta_class ? '</strong>' : '').
                        (\$rosetta_link ? '</a>' : '').
                        '</li>'."\n";
                    }
                    echo '</ul>'."\n";
                  }
            EOT;

        return '<?php ' . $res . ' ?>';
    }

    public static function rosettaEntryWidget($w)
    {
        if (!dcCore::app()->blog->settings->rosetta->active) {
            return;
        }

        if ($w->offline) {
            return;
        }

        $urlTypes = ['post'];
        if (dcCore::app()->plugins->moduleExists('pages')) {
            $urlTypes[] = 'pages';
        }

        if (!in_array(dcCore::app()->url->type, $urlTypes)) {
            return;
        }

        // Get list of available translations for current entry
        $post_type = (dcCore::app()->url->type == 'post' ? 'post' : 'page');
        $current   = '';
        $table     = self::EntryListHelper(dcCore::app()->ctx->posts->post_id, dcCore::app()->ctx->posts->post_lang, $post_type, $w->current, $current);
        if (!$table) {
            return;
        }

        // Render widget title
        $res = ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) . "\n" : '');

        // Render widget list of translations
        $list = '';
        foreach ($table as $name => $url) {
            $link  = ($name != $current || $w->current == 'link');
            $class = ($name == $current ? ' class="current"' : '');

            $list .= '<li' . $class . '>' .
            ($link ? '<a href="' . $url . '">' : '') .
            ($class ? '<strong>' : '') . Html::escapeHTML($name) . ($class ? '</strong>' : '') .
                ($link ? '</a>' : '') .
                '</li>' . "\n";
        }
        if ($list == '') {
            return;
        }
        $res .= '<ul>' . $list . '</ul>' . "\n";

        // Render full content
        return $w->renderDiv($w->content_only, 'rosetta-entries ' . $w->class, '', $res);
    }
}
