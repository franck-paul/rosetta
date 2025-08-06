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

class RowRosetta extends Row
{
    /**
     * @param int $src_id   Source entry ID
     */
    public int $src_id;

    /**
     * @param ?string  $src_lang    Source entry lang
     */
    public ?string $src_lang = null;

    /**
     * @param int $dst_id   Translated entry ID
     */
    public int $dst_id;

    /**
     * @param ?string  $dst_lang    Translated entry lang
     */
    public ?string $dst_lang = null;
}
