<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_jostrava
 *
 * @copyright   Copyright (C) 2016 - 2025 JL TRYOEN, Inc. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        // Register module-specific services here if needed in the future.
    }
};