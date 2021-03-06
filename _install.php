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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$new_version = $core->plugins->moduleInfo('rosetta', 'version');
$old_version = $core->getVersion('rosetta');

if (version_compare($old_version, $new_version, '>=')) {
    return;
}

try
{
    // Database schema
    $s = new dbStruct($core->con, $core->prefix);

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

    $s->rosetta->reference('fk_rosetta_src', 'src_id', 'post', 'post_id', 'cascade', 'cascade');
    $s->rosetta->reference('fk_rosetta_dst', 'dst_id', 'post', 'post_id', 'cascade', 'cascade');

    // Schema installation
    $si      = new dbStruct($core->con, $core->prefix);
    $changes = $si->synchronize($s);

    // Blog settings
    $core->blog->settings->addNamespace('rosetta');

    // Default state is inactive
    $core->blog->settings->rosetta->put('active', false, 'boolean', 'Active', false, true);
    $core->blog->settings->rosetta->put('accept_language', false, 'boolean', 'Take care of browser accept-language', false, true);

    $core->setVersion('rosetta', $new_version);

    return true;
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}
return false;
