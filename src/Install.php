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

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Database schema
            $new_structure = App::db()->structure();

            $new_structure->rosetta
                ->field('src_id', 'bigint', 0, false)
                ->field('src_lang', 'varchar', 5, true, null)
                ->field('dst_id', 'bigint', 0, false)
                ->field('dst_lang', 'varchar', 5, true, null)

                ->primary('pk_rosetta', 'src_id', 'dst_id')
            ;

            $new_structure->rosetta->index('idx_rosetta_src_id', 'btree', 'src_id');
            $new_structure->rosetta->index('idx_rosetta_dst_id', 'btree', 'dst_id');

            // Direct association indexes helpers
            $new_structure->rosetta->index('idx_rosetta_src_id_dst_lang', 'btree', 'src_id', 'dst_lang');
            $new_structure->rosetta->index('idx_rosetta_dst_id_src_lang', 'btree', 'dst_id', 'src_lang');

            // Indirect association indexes helpers
            $new_structure->rosetta->index('idx_rosetta_src_id_src_lang', 'btree', 'src_id', 'src_lang');
            $new_structure->rosetta->index('idx_rosetta_dst_id_dst_lang', 'btree', 'dst_id', 'dst_lang');

            $new_structure->rosetta->reference('fk_rosetta_src', 'src_id', App::blog()::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');
            $new_structure->rosetta->reference('fk_rosetta_dst', 'dst_id', App::blog()::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');

            // Schema installation
            $current_structure = App::db()->structure();
            $current_structure->synchronize($new_structure);

            // Default state is inactive
            $settings = My::settings();
            $settings->put('active', false, App::blogWorkspace()::NS_BOOL, 'Active', false, true);
            $settings->put('accept_language', false, App::blogWorkspace()::NS_BOOL, 'Take care of browser accept-language', false, true);
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }
}
