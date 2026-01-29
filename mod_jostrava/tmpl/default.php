<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_jostrava
 *
 * @copyright   Copyright (C) 2016 - 2025 JL TRYOEN, Inc. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;

$club_id = $params->get('club_id', 'trycoaching');
$strava_url = "https://www.strava.com/clubs/" . $club_id ."/recent_activity";
$imgurl = Uri::root(true) . "/media/mod_jostrava/images";
?>

<div class="mod-mod_jostrava">
    <br>
    <?php if (empty($items)) : ?>
        <p><?php echo htmlspecialchars('No activities found.', ENT_QUOTES, 'UTF-8'); ?></p>
    <?php else : ?>
        <table class="table table-striped"><tbody>
            <?php foreach ($items as $act) :
                $title = htmlspecialchars($act['name'] ?? 'Activity', ENT_QUOTES, 'UTF-8');
                $author  = htmlspecialchars(($act['athlete']['firstname'] ?? '') . " " . ($act['athlete']['lastname'] ?? '') , ENT_QUOTES, 'UTF-8');
                $type  = htmlspecialchars($act['type'] ?? '' ,ENT_QUOTES, 'UTF-8');
                $img = $imgurl . "/" .(in_array($type, array("Swim", "Run", "Ride", "VirtualRide"))? $type :  "triathon") . ".jpg";
            ?>
                <tr>
                   <td><strong><?php echo $author; ?></strong></td>
                   <td><img src="<?php echo $img; ?>"/></td>
                   <td><strong><?php echo $title; ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <img src="<?php echo $imgurl ."/strava.jpg";?>"/>
    <a class="primary" target="_self" href="<?php echo $strava_url;?>">Voir toutes les activités du <em>club</em>&nbsp;»</a>
    <div target="_parent" class="branding logo-sm"><a class="branding-content" target="_parent" href="<?php echo $strava_url;?>"><span class="sr-only">Strava</span></a>
    </div>
    
</div>
