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
use Dotclear\Plugin\TemplateHelper\Code;

class FrontendTemplate
{
    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
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

        return Code::getPHPTemplateValueCode(
            FrontendTemplateCode::rosettaEntryList(...),
            [
                $option,
            ],
            attr: $attr,
        );
    }
}
