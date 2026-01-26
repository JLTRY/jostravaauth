<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_jostravaauth
 *
 * @copyright   Copyright (C) 2016 - 2025 JL TRYOEN, Inc. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace JLTRY\Component\JoStravaAuth\Site\Controller;

use Joomla\OAuth2\Client;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use JLTRY\Component\JoStravaAuth\Site\Helper\JOStravaAuthHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * Registration controller class for Users.
 *
 * @since  1.6
 */
class UserController extends BaseController
{
    private $oauth_client;
    private $Itemid;
    private $log = false;
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *                          Recognized key values include 'name', 'default_task', 'model_path', and
     *                          'view_path' (this list is not meant to be comprehensive).
     *
     * @since   3.5
     */
    public function __construct($config = array())
    {
        $this->log = true;
        JOStravaAuthHelper::addLogger();
        $this->log("construct: JGoogleControllerUser:". print_r($config, true));
        $app = Factory::getApplication();
        $return = $app->input->getString('return', '');
        $session = Factory::getSession();
        if ($return) {
            $session->set('redirecturi', base64_decode($return));
        }
        $this->log("construct:Itemid new client". print_r($ItemId, true));
        $oauth_client = new Client([], null, null, $app, $this);
        $this->log("construct:client OK");
        $oauth_client->setOption('sendheaders',true);
        $oauth_client->setOption('client_id','token');
        $oauth_client->setOption('scope',array("activity:write,read"));
        $oauth_client->setOption('requestparams',
                               array('response_type' => 'code',
                              'approval_prompt' => "force"));
        $params = $app->getParams('com_jostravaauth');
        $oauth_client->setOption('clientid', $params->get('clientid',''));
        $oauth_client->setOption('clientsecret', $params->get('clientsecret',''));
        $oauth_client->setOption('redirecturi', $params->get('redirecturi',''));// . 'return=' . $return);
        $oauth_client->setOption('authurl','https://www.strava.com/oauth/authorize');
        $oauth_client->setOption('tokenurl','https://www.strava.com/api/v3/oauth/token');
        $this->log("construct:end before oauth_client" . $this->ItemId);
        $this->oauth_client = $oauth_client; 
        $this->log("construct:end after oauth_client:" .  $oauth_client->getOption('redirecturi'));
        parent::__construct($config);
        $this->log("construct:end" . $this->ItemId);
    }
    
    
    public function log($str)
    {
        if ($this->log) {
            JOStravaAuthHelper::Log($str);
        }
    }
    
    /**
     * Method to log in a user.
     *
     * @return  void
     *
     * @since   1.6
     */
    public function login()
    {
        JOStravaAuthHelper::Log("login");
        JOStravaAuthHelper::Log("oauth_client:" . print_r(get_class($this->oauth_client), true));
        try {
            $token = $this->oauth_client->authenticate();
            JOStravaAuthHelper::Log("authentificate OK:" . $token);
        }
        catch (\InvalidArgumentException $ex) {
            $app->enqueueMessage(sprintf("Error: %s<br> Please fill parameters for component", $ex->getMessage()), 'error');
            JOStravaAuthHelper::Log("authentificate Error:" . print_r($ex, true));
        }
    }
    
    
    public function auth()
    {
        JOStravaAuthHelper::Log("strava auth");
        $this->oauth_client->setOption('sendheaders',false);
        //sleep to avoid issue with OVH
        $token = $this->oauth_client->authenticate();
        JOStravaAuthHelper::Log("strava auth =>" . print_r($token, true));
        if ($this->oauth_client->isAuthenticated())
        {
            JOStravaAuthHelper::Log("isauthentificated" . print_r($token, true));
        }
    }
}
