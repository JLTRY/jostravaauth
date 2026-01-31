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