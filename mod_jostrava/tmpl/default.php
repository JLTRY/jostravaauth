<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_jostrava
 *
 * @copyright   Copyright (C) 2016 - 2025 JL TRYOEN, Inc. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$titleKey = $params->get('title_field', 'name');
$dateKey  = $params->get('date_field', 'start_date_local');
?>

<div class="mod-mod_jostrava">
    <?php if (empty($items)) : ?>
        <p><?php echo htmlspecialchars('No activities found.', ENT_QUOTES, 'UTF-8'); ?></p>
    <?php else : ?>
        <ul class="mod-jostrava-activities">
            <?php foreach ($items as $act) :
                $title = htmlspecialchars($act[$titleKey] ?? $act['name'] ?? 'Activity', ENT_QUOTES, 'UTF-8');
                $date  = htmlspecialchars($act[$dateKey]  ?? $act['start_date_local'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
                <li>
                    <strong><?php echo $title; ?></strong>
                    <?php if ($date) : ?><span class="date"> — <?php echo $date; ?></span><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>