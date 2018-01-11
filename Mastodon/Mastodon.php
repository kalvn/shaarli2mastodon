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
     * Credentials to use Mastodon API
     * @var array
     */
    protected $appCredentials = [];

    /**
     * Setting Domain, like 'mastodon.social'
     * @param string $domain
     */
    public function __construct($domain = 'mastodon.social', array $parameters)
    {
        $this->domain = $domain;

        $this->http = new HttpRequest($this->domain);

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
            //$this->saveCredentialsToJSON($this->appCredentials);
            return $token['access_token'];
        }
        return false;
    }
}