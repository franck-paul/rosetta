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
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Exception;

/**
 * Rosetta table schema:
 *
 * src_id       post/page ID
 * src_lang     post/page lang
 * dst_id       translated post/page ID
 * dst_lang     translated post/page lang
 *
 * Principe :
 *
 * Les chaînages individuels sont bijectifs
 * Une nouvelle traduction est ajoutée à tous les billets déjà chaînés
 * Une traduction supprimée du chaînage l'est pour tous les billets chaînés
 *
 * Par exemple, si A (fr) et B (en) sont chaînés, une nouvelle traduction C (de) est ajoutée à A et à B
 *
 * ce qui donne la table suivante :
 * A (fr) -> B (en)
 * A (fr) -> C (de)
 * B (en) -> C (de)
 *
 * Avantage : La suppression d'un billet ou d'une page n'entraîne du coup pas de rupture dans la chaîne de traduction.
 * Inconvénient : Le nombre de tuples peut vite grimper (factorielle du nb de langues / chaîne) si on gère beaucoup de langues.
 */
class CoreData
{
    /**
     * Table name
     *
     * @var        string
     */
    public const ROSETTA_TABLE_NAME = 'rosetta';

    /**
     * Add a new translation for a post/page (only if it does not already exists)
     *
     * @param int           $src_id   original post/page id
     * @param null|string   $src_lang original lang
     * @param int           $dst_id   new post/page translation id
     * @param null|string   $dst_lang new post/page translation lang
     *
     * @return bool true if translation have been successfully added, else false
     */
    public static function addTranslation(int $src_id, ?string $src_lang, int $dst_id, ?string $dst_lang): bool
    {
        // Check args
        if ($src_lang == '' || !$src_lang) {
            // Use blog language if language not specified for original post
            $src_lang = App::blog()->settings()->system->lang;
        }

        if ($dst_lang == '' || !$dst_lang) {
            // Use blog language if language not specified for original post
            $dst_lang = App::blog()->settings()->system->lang;
        }

        if ($src_lang == $dst_lang) {
            return false;
        }

        if (self::findTranslation($src_id, $src_lang, $dst_lang) != -1) {
            // translation already attached
            return false;
        }

        // Get all existing translations -> [lang => id]
        $list = self::findAllTranslations($src_id, $src_lang, true);

        // Add the new translation attachment for all existing translations
        try {
            if (!is_array($list)) {
                // No translation yet, add direct one
                App::con()->writeLock(App::con()->prefix() . self::ROSETTA_TABLE_NAME);
                $cur           = App::con()->openCursor(App::con()->prefix() . self::ROSETTA_TABLE_NAME);
                $cur->src_id   = $src_id;
                $cur->src_lang = $src_lang;
                $cur->dst_id   = $dst_id;
                $cur->dst_lang = $dst_lang;
                $cur->insert();
                App::con()->unlock();
            } else {
                foreach ($list as $lang => $id) {
                    if (self::findTranslation($id, $lang, $dst_lang, false) == -1) {
                        // Add the new translation
                        App::con()->writeLock(App::con()->prefix() . self::ROSETTA_TABLE_NAME);
                        $cur           = App::con()->openCursor(App::con()->prefix() . self::ROSETTA_TABLE_NAME);
                        $cur->src_id   = $id;
                        $cur->src_lang = $lang;
                        $cur->dst_id   = $dst_id;
                        $cur->dst_lang = $dst_lang;
                        $cur->insert();
                        App::con()->unlock();
                    }
                }
            }
        } catch (Exception $exception) {
            App::con()->unlock();

            throw $exception;
        }

        return true;
    }

    /**
     * Remove an existing translation for a post/page
     *
     * @param int           $src_id   original post/page id
     * @param null|string   $src_lang original lang
     * @param int           $dst_id   post/page translation id to be removed
     * @param null|string   $dst_lang new post/page translation lang to be removed
     *
     * @return bool  true if translation have been successfully removed, else false
     */
    public static function removeTranslation(int $src_id, ?string $src_lang, int $dst_id, ?string $dst_lang): bool
    {
        // Check args
        if ($src_lang == '' || !$src_lang) {
            // Use blog language if language not specified for original post
            $src_lang = App::blog()->settings()->system->lang;
        }

        if ($dst_lang == '' || !$dst_lang) {
            // Use blog language if language not specified for original post
            $dst_lang = App::blog()->settings()->system->lang;
        }

        if ($src_lang == $dst_lang) {
            return false;
        }

        if (self::findTranslation($src_id, $src_lang, $dst_lang) === -1) {
            // Translation attachment not found
            return false;
        }

        App::con()->writeLock(App::con()->prefix() . self::ROSETTA_TABLE_NAME);

        try {
            // Remove the translations
            $sql = new DeleteStatement();
            $sql
                ->from(App::con()->prefix() . self::ROSETTA_TABLE_NAME)
                ->where($sql->orGroup([
                    $sql->andGroup([
                        'src_id = ' . $dst_id,
                        'src_lang = ' . $sql->quote($dst_lang),
                    ]),
                    $sql->andGroup([
                        'dst_id = ' . $dst_id,
                        'dst_lang = ' . $sql->quote($dst_lang),
                    ]),
                ]))
            ;
            $sql->delete();
            App::con()->unlock();
        } catch (Exception $exception) {
            App::con()->unlock();

            throw $exception;
        }

        return true;
    }

    /**
     * Find direct posts/pages associated with a post/page id and lang
     *
     * @param  int          $id   original post/page id
     * @param  null|string  $lang original lang
     * @param  bool         $full result should include original post/page+lang
     *
     * @return bool|array<string, int>         associative array (lang => id), false if nothing found
     */
    private static function findDirectTranslations(int $id, ?string $lang, bool $full = false): array|bool
    {
        if ($lang == '' || !$lang) {
            // Use blog language if language not specified for original post
            $lang = App::blog()->settings()->system->lang;
        }

        $sql = new SelectStatement();
        $sql
            ->from(App::con()->prefix() . self::ROSETTA_TABLE_NAME)
            ->where($sql->orGroup([
                $sql->andGroup([
                    'src_id = ' . $id,
                    'src_lang = ' . $sql->quote($lang),
                ]),
                $sql->andGroup([
                    'dst_id = ' . $id,
                    'dst_lang = ' . $sql->quote($lang),
                ]),
            ]))
            ->order('dst_id DESC')
        ;

        $rs = $sql->select();
        if ($rs && $rs->count()) {
            /**
             * @var array<string, int>
             */
            $list = [];
            while ($rs->fetch()) {
                // Add src couple if requested
                if (($full) || ($rs->src_id != $id || $rs->src_lang != $lang)) {
                    $list[(string) $rs->src_lang] = (int) $rs->src_id;
                }

                // Add dst couple if requested
                if (($full) || ($rs->dst_id != $id || $rs->dst_lang != $lang)) {
                    $list[(string) $rs->dst_lang] = (int) $rs->dst_id;
                }
            }

            return $list;
        }

        // Nothing found
        return false;
    }

    /**
     * Find all posts/pages associated with a post/page id and lang
     *
     * @param  int          $id           original post/page id
     * @param  null|string  $lang         original lang
     * @param  bool         $full         result should include original post/page+lang
     *
     * @return array<string, int>|bool    associative array (lang => id), false if nothing found
     */
    public static function findAllTranslations(int $id, ?string $lang, bool $full = false): array|bool
    {
        if ($lang == '' || !$lang) {
            // Use blog language if language not specified for original post
            $lang = App::blog()->settings()->system->lang;
        }

        // Get direct associations
        $list = self::findDirectTranslations($id, $lang, true);
        if (is_array($list)) {
            // Get indirect associations
            $ids = [];
            foreach ($list as $l => $i) {
                $ids[] = [$l => $i];
            }

            while (count($ids)) {
                $pair = array_shift($ids);
                foreach ($pair as $l => $i) {
                    $next = self::findDirectTranslations($i, $l, true);
                    if (is_array($next)) {
                        foreach ($next as $key => $value) {
                            if (!in_array($value, $list, true)) {
                                $list[$key] = $value;
                                $ids[]      = [$key => $value];
                            }
                        }
                    }
                }
            }

            if (!$full && $key = array_search($id, $list, true)) {
                // Remove original from list
                unset($list[$key]);
            }

            return $list;
        }

        // Nothing found
        return false;
    }

    /**
     * Find a post/page id with the requested lang
     *
     * @param  int          $src_id   original post/page id
     * @param  null|string  $src_lang original lang
     * @param  string       $dst_lang requested lang
     * @param  bool         $indirect look also for indirect associations
     *
     * @return int           first found id, -1 if none
     */
    public static function findTranslation(int $src_id, ?string $src_lang, string $dst_lang, bool $indirect = true): int
    {
        if ($src_lang == '' || !$src_lang) {
            // Use blog language if language not specified for original post
            $src_lang = App::blog()->settings()->system->lang;
        }

        // Looks for a post/page with an association with the corresponding lang
        $sql = new SelectStatement();
        $sql
            ->from(App::con()->prefix() . self::ROSETTA_TABLE_NAME)
            ->where($sql->orGroup([
                $sql->andGroup([
                    'src_id = ' . $src_id,
                    'dst_lang = ' . $sql->quote($dst_lang),
                ]),
                $sql->andGroup([
                    'dst_id = ' . $src_id,
                    'src_lang = ' . $sql->quote($dst_lang),
                ]),
            ]))
            ->order('dst_id DESC')
        ;

        $rs = $sql->select();

        if ($rs && $rs->count()) {
            // Load first record
            $rs->fetch();

            // Return found ID
            return $rs->src_id == $src_id ? $rs->dst_id : $rs->src_id;
        }

        if ($indirect) {
            // Looks for an indirect post/page association, ie a -> b and b-> c in table, src = b, looking for c
            $list = self::findAllTranslations($src_id, $src_lang, false);
            if (is_array($list) && array_key_exists($dst_lang, $list)) {
                return $list[$dst_lang];
            }
        }

        // No record found
        return -1;
    }
}
