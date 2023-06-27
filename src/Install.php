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

use dcBlog;
use dcCore;
use dcNamespace;
use dcNsProcess;
use Dotclear\Database\Structure;
use Exception;

class Install extends dcNsProcess
{
    protected static $init = false; /** @deprecated since 2.27 */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::INSTALL);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            // Database schema
            $new_structure = new Structure(dcCore::app()->con, dcCore::app()->prefix);

            $new_structure->rosetta
                ->src_id('bigint', 0, false)
                ->src_lang('varchar', 5, true, null)
                ->dst_id('bigint', 0, false)
                ->dst_lang('varchar', 5, true, null)

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

            $new_structure->rosetta->reference('fk_rosetta_src', 'src_id', dcBlog::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');
            $new_structure->rosetta->reference('fk_rosetta_dst', 'dst_id', dcBlog::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');

            // Schema installation
            $current_structure = new Structure(dcCore::app()->con, dcCore::app()->prefix);
            $current_structure->synchronize($new_structure);

            // Default state is inactive
            $settings = dcCore::app()->blog->settings->get(My::id());
            $settings->put('active', false, dcNamespace::NS_BOOL, 'Active', false, true);
            $settings->put('accept_language', false, dcNamespace::NS_BOOL, 'Take care of browser accept-language', false, true);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }
}
