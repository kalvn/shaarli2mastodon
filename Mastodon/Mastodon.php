<?php
/**
 * Adapted from TootoPHP.
 * 
 * @link https://framagit.org/MaxKoder/TootoPHP
 */
require_once 'HttpRequest.php';

class Mastodon
{

    /**
     * Mastodon Instance Name, like 'mastodon.social'
     * @var string
     */
    protected $domain;
    
    /**
     * HttpRequest Instance
     * @var \HttpRequest
     */
    protected $http;

    /**
     * Defaults headers for HttpRequest
     * @var array
     */
    protected $headers = [
        'Content-Type' => 'application/json; charset=utf-8',
        'Accept'       => '*/*'
    ];

    /**
     * Credentials to register App
     * @var array
     */
    protected $credentials = [
        'client_name'   => '',
        'redirect_uris' => 'urn:ietf:wg:oauth:2.0:oob',
        'scopes'        => 'read write follow',
        'website'       => ''
    ];

    /**
     * Credentials to use Mastodon API
     * @var array
     */
    protected $appCredentials = [];

    /**
     * Authenticated User infos
     * @var array
     */
    private $userInfos = [];

    /**
     * Setting Domain, like 'mastodon.social'
     * @param string $domain
     */
    public function __construct($domain = 'mastodon.social', array $parameters)
    {
        $this->domain = $domain;

        $this->http = new HttpRequest($this->domain);

        $this->credentials['client_name'] = $parameters['client_name'];
        $this->credentials['website'] = $parameters['website'];
        $this->appCredentials['bearer'] = $parameters['bearer'];
        $this->headers['Authorization'] = $parameters['bearer'];
    }

    /**
     * Authentify User
     * 
     * You have to authentify to API before using Mastodon
     * 
     * @param type $userName
     * @param type $password
     */
    public function authentify($userName, $password)
    {
        return $this->authUser($userName, $password);
    }

    /**
     * Get authenticated user
     * 
     * @return array
     */
    public function getUser()
    {
        if (empty($this->userInfos)) {
            $user = $this->http->get(
                $this->http->apiURL . 'accounts/verify_credentials',
                $this->headers,
                null
            );
            if (is_array($user) && isset($user['username'])) {
                $this->userInfos = $user;
            }
            else {
                return false;
            }
        }
        return $this->userInfos;
    }
    
    /**
     * Get authenticated user ID
     * 
     * @return string
     */
    public function getUserID()
    {
        $user = $this->getUser();
        if (!is_array($user)) {
            return false;
        }
        return $user['id'];
    }

    /**
     * Get authenticated user favourites
     * 
     * Return as an array all user's favourites status
     * 
     * @return array
     */
    public function getFavourites()
    {
        return $this->http->get(
            $this->http->apiURL . 'favourites', 
            $this->headers, 
            null
        );
    }
    
    /**
     * Get authenticated user's notifications
     * Return as an array all user's notifications
     * 
     * @return array
     */
    public function getNotifications()
    {
        return $this->http->get(
            $this->http->apiURL . 'notifications', 
            $this->headers, 
            null
        );
    }

    /**
     * Get an account by his ID
     * 
     * Return account as an array
     * 
     * @param string $id
     * @return array
     */
    public function getAccount($id)
    {
        return $this->http->get(
            $this->http->apiURL . 'accounts/' . $id, 
            $this->headers,
            null
        );
    }

    /**
     * Post a new status
     * 
     * Post a new status in Mastodon instance
     * 
     * Return entire status as an array
     * 
     * @param string $content Toot content
     * @param string $visibility Toot visibility (optionnal)
     * Values are :
     * - public
     * - unlisted
     * - private
     * - direct
     * @param array $medias Medias IDs
     * @return array
     */
    public function postStatus($content, $visibility = 'public', $medias = [])
    {
        //var_dump($this->headers);die;
        return $this->http->post(
            $this->http->apiURL . 'statuses',
            $this->headers,
            [
                'status' => $content,
                'visibility' => $visibility,
                'media_ids' => $medias
            ]
        );
    }
    
    /**
     * Get user's followers by his ID
     * If not ID is given, return the authenticated user's followers
     * 
     * Return all followers as an array
     * 
     * @param string $id
     * @param array  $params
     * Params is optionnal associative array with these keys :
     * limit : Maximum number of accounts to get (Default 40, Max 80)
     * max_id : Get a list of followers with ID less than or equal this value
     * since_id : Get a list of followers with ID greater than this value
     * @return array
     */
    public function getFollowers($id = false, $params = [])
    {
        if ($id === false) {
            $id = $this->getUserID();
        }
        return $this->http->get(
            $this->http->apiURL . 'accounts/' . $id . '/followers',
            $this->headers,
            $params
        );
    }
    
    /**
     * Get user's following by his ID
     * If not ID is given, return the authenticated user's following
     * 
     * Return all followings as an array
     * 
     * @param string $id
     * @return array
     */
    public function getFollowing($id = false)
    {
        if ($id === false) {
            $id = $this->getUserID();
        }
        return $this->http->get(
            $this->http->apiURL . 'accounts/' . $id . '/following',
            $this->headers,
            null
        );
    }
    
    /**
     * Get user's statuses by his ID
     * If not ID is given, return the authenticated user's statuses
     * 
     * Return all statuses as an array
     * 
     * @param string $id
     * @param array  $params
     * Params is optionnal associative array with these keys :
     * only_media : Only return statuses that have media attachments
     * exclude_replies : Skip statuses that reply to other statuses
     * @return array
     */
    public function getStatuses($id = false, $params = [])
    {
        if ($id === false) {
            $id = $this->getUserID();
        }
        return $this->http->get(
            $this->http->apiURL . 'accounts/' . $id . '/statuses',
            $this->headers,
            $params
        );
    }

    /**
     * Upload a media from drive to use it in a toot
     * 
     * @param string $filePath Path to the media
     * @return array
     */
    public function createAttachement($filePath)
    {        
        $mimeType = mime_content_type($filePath);
        $curlFile = curl_file_create( $filePath, $mimeType, 'file' );
 
        $url = $this->http->domainURL . $this->http->apiURL . 'media';
        $headers = [
            'Content-Type' => 'multipart/form-data',
        ];
 
        $postField =[
            'access_token'  => $this->appCredentials['bearer'],
            'file'          => $curlFile,
        ];
 
        $opts = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postField,
        ];
 
        $curlHandle = curl_init();
        curl_setopt_array( $curlHandle, $opts );
        $response = curl_exec( $curlHandle );
        curl_close( $curlHandle );
 
        return json_decode( $response, true );
    }
    
    /**
     * Authentify user
     * 
     * Authentify user to get access token
     * If successfull, save token and return it
     * 
     * @param string $userName
     * @param string $password
     * @return boolean|string
     */
    protected function authUser($userName = null, $password = null)
    {
        if (!empty($userName) && !empty($password)) {
            if (is_array($this->appCredentials) && isset($this->appCredentials['client_id'])) {
                // Get authentified token
                $token = $this->http->post(
                    'oauth/token',
                    $this->headers,
                    [
                        'grant_type'    => 'password',
                        'client_id'     => $this->appCredentials['client_id'],
                        'client_secret' => $this->appCredentials['client_secret'],
                        'username'      => $userName,
                        'password'      => $password,
                    ]
                );
                // Save our Token
                return $this->catchBearerToken($token);
            }
        }
        return false;
    }

    /**
     * Catch the 'access_token' from a token
     * 
     * Get access token to save it from a token
     * Return Access Token
     * 
     * @param array $token
     * @return boolean|string
     */
    protected function catchBearerToken($token = null)
    {
        if (!empty($token) && isset($token['access_token'])) {
            // Add Token to AppCredentials and save it
            $this->appCredentials['bearer'] = $token['access_token'];
            $this->headers['Authorization'] = 'Bearer ' . $token['access_token'];
            $this->saveCredentialsToJSON($this->appCredentials);
            return $token['access_token'];
        }
        return false;
    }

    /**
     * Create new App
     * 
     * Create new application with credentials given in 'registerApp' method
     * and save IDs in the JSON file to use them later.
     * 
     * Return true if success
     * 
     * @return boolean
     */
    protected function createApp()
    {
        $credentials = $this->http->post($this->http->apiURL . 'apps', $this->headers, $this->credentials);
        if ($credentials === false) {
            return false;
        }
        return $this->saveCredentialsToJSON($credentials);
    }
}