<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_jostravaauth
 *
 * @copyright   Copyright (C) 2005 - 2016 JL Tryoen, Inc. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace JLTRY\Component\JoStravaAuth\Site\Helper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * JGoogle component helper.
 *
 * @param   string  $submenu  The name of the active view.
 *
 * @return  void
 *
 * @since   1.6
 */
abstract class JOStravaAuthHelper
{
    public static function jsonAnswer($data)
    {
        ob_end_clean();
        header('Content-Type: application/json');
        header('Cache-Control: max-age=120, private, must-revalidate');
        header('Content-Disposition: attachment; filename="jogallery.json"');
        ob_end_clean();
        echo $data;
        Factory::getApplication()->close();
    }

    public static function addLogger() {
        Log::addLogger(
            array(
             // Sets file name.
             'text_file' => 'com_jostravaauth.php',
             // Sets the format of each line.
             'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}'
            ),
            // Sets all but DEBUG log level messages to be sent to the file.
            Log::ALL,
            // The log category which should be recorded in this file.
            array('com_jostravaauth')
        );
    }

    public static function Log($msg, $type = Log::WARNING ){
        Log::add($msg, $type, 'com_jostravaauth');
    }
}

