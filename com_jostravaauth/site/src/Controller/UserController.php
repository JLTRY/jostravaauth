<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_jostravaauth
 *
 * @copyright   Copyright (C) 2016 - 2025 JL TRYOEN, Inc. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace JLTRY\Component\JoStravaAuth\Site\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\OAuth2\Client;
use Joomla\Registry\Registry;

use JLTRY\Component\JoStravaAuth\Site\Helper\JOStravaAuthHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * Registration controller class for Users.
 *
 * @since  1.6
 */
class UserController extends BaseController {
    private $oauth_client;
    private $log = false;
    private $stored_token = NULL;
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
        $this->log("construct: JoStravaControllerUser:". print_r($config, true));
        $app = Factory::getApplication();
        $return = $app->input->getString('return', '');
        $session = Factory::getSession();
        if ($return) {
            $session->set('redirecturi', base64_decode($return));
        }
        $this->log("construct: new client");
        $oauth_client = new Client([], null, null, $app, $this);
        $this->log("construct:client OK");
        $oauth_client->setOption('sendheaders',true);
        $oauth_client->setOption('userefresh', true);
        $oauth_client->setOption('client_id','token');
        $oauth_client->setOption('scope',array("activity:write,read,read_all,profile:read_all"));
        $oauth_client->setOption('requestparams',
                                 array('response_type' => 'code', 'approval_prompt' => "force"));
        $params = $app->getParams('com_jostravaauth');
        $oauth_client->setOption('clientid', $params->get('clientid',''));
        $oauth_client->setOption('clientsecret', $params->get('clientsecret',''));
        $oauth_client->setOption('redirecturi', $params->get('redirecturi',''));// . 'return=' . $return);
        $oauth_client->setOption('authurl','https://www.strava.com/oauth/authorize');
        $oauth_client->setOption('tokenurl','https://www.strava.com/api/v3/oauth/token');
        $this->log("construct:end before oauth_client");
        $this->oauth_client = $oauth_client; 

        // Try to restore a stored token (from session, component params or user params)
        $stored = $this->loadStoredToken();
        if ($stored) {
            $this->log("construct:found stored token");
            // If token was saved as JSON string, decode it
            if (is_string($stored)) {
                $stored = json_decode($stored, true);
            }
            if (is_array($stored)) {
                $this->stored_token = $stored;
                $this->oauth_client->setToken($stored);
            }
        }
        $this->log("construct:end after oauth_client:" .  $oauth_client->getOption('redirecturi'));
        parent::__construct($config);
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
        $input = Factory::getApplication()->input;
        $force = $input->get('force', 0);
        // Try to reuse a stored token if available
        $stored = $this->loadStoredToken();
        if ($stored && ($force == 0)) {
            JOStravaAuthHelper::Log("login:stored");
            if (is_string($stored)) {
                $stored = json_decode($stored, true);
            }

            // If token valid, attach and return
            if ($this->tokenIsValid($stored)) {
                JOStravaAuthHelper::Log("Using stored token, skipping authenticate");
                $this->stored_token = $stored;
                $this->oauth_client->setToken($stored);
                if (!empty($stored['access_token'])) {
                    $this->oauth_client->setOption('access_token', $stored['access_token']);
                }
                if (!empty($stored['refresh_token'])) {
                    $this->oauth_client->setOption('refresh_token', $stored['refresh_token']);
                }
                JOStravaAuthHelper::Log("login:valid token attached");
                return;
            }

            // Token expired: try refreshing if we have a refresh_token
            if (!empty($stored['refresh_token'])) {
                JOStravaAuthHelper::Log("Stored token expired, attempting to refresh");
                $refreshed = $this->refreshToken($stored['refresh_token']);
            }
        }

        JOStravaAuthHelper::Log("oauth_client:" . print_r(get_class($this->oauth_client), true));
        try {
            $token = $this->oauth_client->authenticate();
        }
        catch (\InvalidArgumentException $ex) {
            $app = Factory::getApplication();
            $app->enqueueMessage(sprintf("Error: %s<br> Please fill parameters for component", $ex->getMessage()), 'error');
            JOStravaAuthHelper::Log("authentificate Error:" . print_r($ex, true));
        }
    }
    
    
    public function auth()
    {
        JOStravaAuthHelper::Log("strava auth");
        $this->oauth_client->setOption('sendheaders',false);

        $token = $this->oauth_client->authenticate();
        JOStravaAuthHelper::Log("strava auth =>" . print_r($token, true));

        if ($this->oauth_client->isAuthenticated())
        {
            JOStravaAuthHelper::Log("isauthentificated" . print_r($token, true));
            // Persist token for reuse
            $this->saveToken($token);
        }
    }

    /**
     * Save token into session and into component params (and user params if logged in)
     * so it persists across visits and for public users.
     *
     * @param  mixed  $token
     *
     * @return void
     */
    private function saveToken($token)
    {
        try {
            // Normalize token: if string try decode
            if (is_string($token)) {
                $tokenArray = json_decode($token, true);
            } elseif (is_object($token)) {
                $tokenArray = json_decode(json_encode($token), true);
            } else {
                $tokenArray = (array) $token;
            }
            
            // Ensure created for expires_in calculation
            if (!isset($tokenArray['created'])) {
                $tokenArray['created'] = time();
            }
            // If expires_in present but not expires_at, compute expires_at
            if (!empty($tokenArray['expires_in']) && empty($tokenArray['expires_at'])) {
                $tokenArray['expires_at'] = (int) $tokenArray['created'] + (int) $tokenArray['expires_in'];
            }
            $this->stored_token = $tokenArray;
            // Save to session
            $session = Factory::getSession();
            $session->set('com_jostravaauth.token', $tokenArray);
            $this->log("saveToken: saved in session");

            $db = Factory::getDbo();

            // Save into component params (#__extensions.params where element = 'com_jostravaauth')
            try {
                $query = $db->getQuery(true)
                    ->select($db->quoteName('params'))
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote('com_jostravaauth'));

                $db->setQuery($query);
                $existing = $db->loadResult();

                $extParams = json_decode($existing, true);
                if (!is_array($extParams)) {
                    $extParams = [];
                }

                $extParams['token'] = $tokenArray;
                $extParams['token_saved_at'] = time();

                $json = json_encode($extParams);

                $update = $db->getQuery(true)
                    ->update($db->quoteName('#__extensions'))
                    ->set($db->quoteName('params') . ' = ' . $db->quote($json))
                    ->where($db->quoteName('element') . ' = ' . $db->quote('com_jostravaauth'));

                $db->setQuery($update)->execute();
                $this->log("saveToken: saved in component params");
            } catch (\Exception $e) {
                $this->log("saveToken: component params save failed: " . $e->getMessage());
            }

        } catch (\Exception $e) {
            $this->log("saveToken error: " . $e->getMessage());
        }
    }

    /**
     * Load stored token from session, component params, or user params (session preferred).
     *
     * @return mixed|null
     */
    private function loadStoredToken()
    {
        $token = null;
        try {
            $session = Factory::getSession();
            $token = $session->get('com_jostravaauth.token', null);
            if ($token) {
                $this->log("loadStoredToken: token found in session");
            }
        } catch (\Exception $e) {
            $this->log("loadStoredToken error: " . $e->getMessage());
        }
        if ($token == null) {
            // Try component params first (useful for public/no-user flows)
            try {
                $db = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select($db->quoteName('params'))
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote('com_jostravaauth'));

                $db->setQuery($query);
                $existing = $db->loadResult();
                if (!empty($existing)) {
                    $extParams = json_decode($existing, true);
                    if (is_array($extParams) && !empty($extParams['token'])) {
                        $this->log("loadStoredToken: token found in component params" . print_r($extParams[0]['token'], true));
                        $token = $extParams['token'];
                    }
                }
            } catch (\Exception $e) {
                $this->log("loadStoredToken: component params read failed: " . $e->getMessage());
            }
        }
        if ($token && array_key_exists('saved_at', $token))
        {
            $token['created'] = $token['saved_at'];
        }
        $this->stored_token = $token;
        return $token;
    }

    /**
     * Very small validation for token expiry.
     *
     * Looks for `expires_at`, `expires`, or `expires_in` keys to determine if token is still valid.
     *
     * @param mixed $token
     * @return bool
     */
    private function tokenIsValid($token)
    {
        if (!$token) {
            $this->log("tokenIsValid null");
            return false;
        }
        if (is_string($token)) {
            $token = json_decode($token, true);
        }
        if (!is_array($token)) {
            $this->log("tokenIsValid not an array");
            return false;
        }

        $now = time();

        if (!empty($token['expires_at'])) {
            $this->log(sprintf("tokenIsValid expires_at %d now %d", (int) $token['expires_at'], $now));
            return ((int) $token['expires_at'] > $now);
        }
        if (!empty($token['expires_in'])) {
            // if created present, compute
            if (!empty($token['created'])) {
                 $this->log(sprintf("tokenIsValid save_at  %d + %d now %d", (int) $token['created'], $token['expires_in'], $now));
                return (((int) $token['created'] + (int) $token['expires_in']) > $now);
            }
            // otherwise conservatively assume valid (can't verify)
            $this->log(sprintf("tokenIsValid assume alid"));
            return true;
        }

        // No expiry info available: assume valid (or implement your own policy)
        $this->log("tokenIsValid no expiry info" . print_r($token,true));
        return true;
    }

    /**
     * Refresh an access token using the refresh_token via Strava token endpoint.
     *
     * @param string $refreshToken
     * @return array|null
     */
    private function refreshToken()
    {
        if ($this->stored_token == null) return false;
        $token = $this->oauth_client->refreshToken();
        if ($token) {
            $this->saveToken($token);
        }
    }

    private function getResponse($path)
    {
        $this->log("getResponse");
        $valid = $this->tokenIsValid($this->stored_token);
        if ($valid) {
            $this->log("getResponse : token is valid");
            $this->log(print_r($this->stored_token, true));
        } elseif ($this->refreshToken()) {
            $this->log("getResponse : token has been refreshed");
        } else {
            return "Error of token please log";
        }
        $this->log("getResponse start stored_token:" . print_r($this->stored_token['access_token'], true));
        $url = 'https://www.strava.com/api/v3/' . $path;
        $this->log("getResponse url:" . $url);
        $error = true;
        $data = null;
        try 
        {
            $response = $this->oauth_client->query($url, array(), $headers);
            $this->log("getResponse :" . $response->getBody());
            $error = false;
            $message = 'Data retrieved successfully';
            $data = json_decode($response->getBody());
        }
        catch(UnexpectedResponseException $e)
        {
            $this->log("getResponse UnexpectedResponseException:" . $e->getMessage());
            $message = $e->getMessage();
        }
        catch(Exception $e)
        {
            $this->log("getResponse exception:" . $e->getMessage());
            $message = $e->getMessage();
        }
        return JOStravaAuthHelper::jsonAnswer(json_encode(new JsonResponse($data, $message , $error)));
    }

    public function getClubMembers()
    {
        $input = Factory::getApplication()->input;
        $club = $input->get('club_id', 'trycoaching');
        $path = 'clubs/'. $club . '/members';
        return $this->getResponse($path);
    }
    
    public function getClubActivities()
    {
        $input = Factory::getApplication()->input;
        $club_id = $input->get('club_id', 'trycoaching');
        $path = 'clubs/'. $club_id .'/activities?page=1&per_page=5';
        return $this->getResponse($path);
    }
    
    public function getAthlete()
    {
        $input = Factory::getApplication()->input;
        $id = $input->get('id', null);
        $path= 'athlete';
        if ($id) {
            $path .= '/' . $id;
        }
        return $this->getResponse($path);
    }
};
