<?php
/**
 * @brief rosetta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\rosetta;

use ArrayObject;
use Dotclear\Helper\Html\Html;

class FrontendTemplate
{
    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     *
     * @return     string
     */
    public static function rosettaEntryList(array|ArrayObject $attr): string
    {
        $settings = My::settings();
        if (!$settings->active) {
            return '';
        }

        $option = empty($attr['include_current']) ? 'std' : (string) $attr['include_current'];

        if (!preg_match('#^(std|link|none)$#', $option)) {
            $option = 'std';
        }

        $html  = Html::class;
        $class = FrontendHelper::class;

        $res = <<<EOT
                  \$rosetta_table = {$class}::EntryListHelper(
                    App::frontend()->context()->posts->post_id,App::frontend()->context()->posts->post_lang,App::frontend()->context()->posts->post_type,
                    '{$option}',\$rosetta_current);
                  if (is_array(\$rosetta_table) && count(\$rosetta_table)) {
                    echo '<ul class="rosetta-entries-list">'."\n";
                    foreach (\$rosetta_table as \$rosetta_name => \$rosetta_url) {
                      \$rosetta_link = (\$rosetta_name != \$rosetta_current || '{$option}' == 'link');
                      \$rosetta_class = (\$rosetta_name == \$rosetta_current ? 'class="current"' : '');
                      echo '<li'.\$rosetta_class.'>'.
                        (\$rosetta_link ? '<a href="'.\$rosetta_url.'">' : '').
                        (\$rosetta_class ? '<strong>' : '').{$html}::escapeHTML(\$rosetta_name).(\$rosetta_class ? '</strong>' : '').
                        (\$rosetta_link ? '</a>' : '').
                        '</li>'."\n";
                    }
                    echo '</ul>'."\n";
                  }
            EOT;

        return '<?php ' . $res . ' ?>';
    }
}
