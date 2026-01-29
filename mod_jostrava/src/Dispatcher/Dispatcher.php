<?php
/*------------------------------------------------------------------------
# mod_jostrava - JO's Strava module
# ------------------------------------------------------------------------
# author    JL TRYOEN
# Copyright (C) 2026 www.jltryoen.fr All Rights Reserved.
# @license  http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
# Websites: http://www.jltryoen.fr
-------------------------------------------------------------------------*/

namespace JLTRY\Module\JOStrava\Site\Dispatcher;

// No direct access
defined('_JEXEC') or die;


use JLTRY\Module\JOStrava\Site\Helper\ModJoStravaHelper;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Log\Log;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_articles
 *
 * @since  5.2.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;
    protected function getLayoutData(): array    {        $data      = parent::getLayoutData();        $params    = $data['params'];        $layout    = $params->get('layout', 'default');
        $clubId    = $params->get('club_id', 'trycoaching');
        $limit     = (int) $params->get('limit', 10);
        Log::add("mod_jostrava getLayoutData", Log::WARNING, 'mod_jostrava');
        $items     = ModJoStravaHelper::getClubActivities($clubId, $limit);        $this->module->cacheTime = 0;
                return array( 'module'   => $this->module,                      'app'      => $this->app,                      'input'    => $this->input,                      'params'   => new Registry($params),                      'clubid'   => $clubId,                      'items'    => $items);    }}?>
