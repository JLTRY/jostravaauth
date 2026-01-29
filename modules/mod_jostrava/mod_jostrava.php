<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_jostrava
 *
 * @copyright   Copyright (C) 2016 - 2025 JL TRYOEN, Inc. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

require_once __DIR__ . '/helper.php';

$clubId    = $params->get('club_id', '');
$limit     = (int) $params->get('limit', 10);
$titleKey  = $params->get('title_field', 'name');
$dateKey   = $params->get('date_field', 'start_date_local');

$items = ModJoStravaHelper::getClubActivities($clubId, $limit);

require ModuleHelper::getLayoutPath('mod_jostrava', $params->get('layout', 'default'));