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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

if (!dcCore::app()->newVersion(basename(__DIR__), dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version'))) {
    return;
}

try {
    // Database schema
    $s = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);

    $s->rosetta
        ->src_id('bigint', 0, false)
        ->src_lang('varchar', 5, true, null)
        ->dst_id('bigint', 0, false)
        ->dst_lang('varchar', 5, true, null)

        ->primary('pk_rosetta', 'src_id', 'dst_id')
    ;

    $s->rosetta->index('idx_rosetta_src_id', 'btree', 'src_id');
    $s->rosetta->index('idx_rosetta_dst_id', 'btree', 'dst_id');

    // Direct association indexes helpers
    $s->rosetta->index('idx_rosetta_src_id_dst_lang', 'btree', 'src_id', 'dst_lang');
    $s->rosetta->index('idx_rosetta_dst_id_src_lang', 'btree', 'dst_id', 'src_lang');

    // Indirect association indexes helpers
    $s->rosetta->index('idx_rosetta_src_id_src_lang', 'btree', 'src_id', 'src_lang');
    $s->rosetta->index('idx_rosetta_dst_id_dst_lang', 'btree', 'dst_id', 'dst_lang');

    $s->rosetta->reference('fk_rosetta_src', 'src_id', dcBlog::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');
    $s->rosetta->reference('fk_rosetta_dst', 'dst_id', dcBlog::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');

    // Schema installation
    $si      = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
    $changes = $si->synchronize($s);

    // Default state is inactive
    dcCore::app()->blog->settings->rosetta->put('active', false, 'boolean', 'Active', false, true);
    dcCore::app()->blog->settings->rosetta->put('accept_language', false, 'boolean', 'Take care of browser accept-language', false, true);

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
