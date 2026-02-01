<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_jostrava
 *
 * @copyright   Copyright (C) 2016 - 2025 JL TRYOEN, Inc. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace JLTRY\Module\JOStrava\Site\Helper;
defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Log\Log;

class ModJoStravaHelper
{
     /**
     * Remplace les smileys en texte par des entités HTML (Unicode) ou des images.
     * @param string $text Le texte contenant les smileys.
     * @return string Le texte avec les smileys remplacés.
     */
    public static function replaceSmileys($text) {
        $smileys = array(
            // Remplacement par des entités HTML (Unicode)
            ':)'      => '&#128522;',  // 😊 (sourire)
            ':-)'     => '&#128522;',  // 😊
            ';)'      => '&#128521;',  // 😉 (clin d'œil)
            ';-)'     => '&#128521;',  // 😉
            ':D'      => '&#128515;',  // 😃 (grand sourire)
            ':-D'     => '&#128515;',  // 😃
            ':('      => '&#128532;',  // 😢 (triste)
            ':-('     => '&#128532;',  // 😢
            ':/'      => '&#128528;',  // 😮 (dubitatif)
            ':-/'     => '&#128528;',  // 😮
            ':P'      => '&#128539;',  // 😛 (tire la langue)
            ':-P'     => '&#128539;',  // 😛
            'xD'      => '&#128518;',  // 😆 (mort de rire)
            ':O'      => '&#128558;',  // 😲 (surpris)
            ':-O'     => '&#128558;',  // 😲
            '<3'      => '❤️',        // ❤️ (cœur)
            ':*'      => '&#128535;',  // 😘 (bise)
            ':-*'     => '&#128535;',  // 😘
            ':\''     => '&#128531;',  // 😓 (gêne)
            ':-|'     => '&#128529;',  // 😐 (neutre)
            'B)'      => '&#128526;',  // 😎 (cool)
            'B-)'     => '&#128526;',  // 😎

            // Remplacement par des images (optionnel, décommentez si besoin)
            // ':)'   => '<img src="/media/mod_jostrava/images/smile.png" alt="smile" width="20" />',
            // ';)'   => '<img src="/media/mod_jostrava/images/wink.png" alt="wink" width="20" />',
            // ':D'   => '<img src="/media/mod_jostrava/images/grinning.png" alt="grinning" width="20" />',
        );

        return str_replace(array_keys($smileys), array_values($smileys), $text);
    }


    /**
     * Fetch club activities by calling the component task user.getClubActivities
     *
     * Note: This performs a server-side HTTP GET to the site's index.php URL.
     * Server-side requests do not include the browser session/cookies; if the
     * component action requires the logged-in user's session, this will fail or
     * return an unauthenticated response. Alternative approaches are listed below.
     *
     * @param mixed $clubId
     * @param int $limit
     * @return array
     */
    public static function getClubActivities($clubId, $limit = 10)
    {
        if (empty($clubId)) {
            Log::add("mod_jostrava empty clubId", Log::WARNING, 'com_jostravaauth');
            return [];
        }

        try {
            // Build the URL to the component task; return format JSON
            $query = 'index.php?option=com_jostravaauth&task=user.getClubActivities'
                   . '&club_id=' . $clubId;
                /*. '&limit=' . intval($limit)
                . '&format=json';*/

            $url = Uri::root() . ltrim($query, '/');
            Log::add("mod_jostrava get Url" . $url, Log::WARNING, 'com_jostravaauth');
            $http = HttpFactory::getHttp();
            $response = $http->get($url);

            if ($response->code !== 200) {
                Log::add(sprintf('mod_jostrava: component returned HTTP %d for %s', $response->code, $url), Log::WARNING, 'com_jostravaauth');
                return [];
            }

            $jsondata = json_decode($response->body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::add('mod_jostrava: JSON decode error: ' . json_last_error_msg(), Log::WARNING, 'com_jostravaauth');
                return [];
            }
            if ($jsondata["success"] == 1) {
                $data = $jsondata["data"];
                return $data;
            } else {
                return $jsondata['message'];
            }
        } catch (\Exception $e) {
            Log::add('mod_jostrava: Exception while fetching activities: ' . $e->getMessage(), Log::ERROR, 'com_jostravaauth');
            return [];
        }
    }
}