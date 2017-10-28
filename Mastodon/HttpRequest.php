<?php

/**
 * HttpRequest
 * 
 * @author Maxence Cauderlier
 * @link http://max-koder.fr
 * @link https://framagit.org/MaxKoder/TootoPHP
 * @package TootoPHP
 * @version 1.0.0
 * @license http://opensource.org/licenses/MIT The MIT License  
 */

/**
 * HttpRequest is a way to do http requests and get responses
 */

class HttpRequest
{

    /**
     * Mastodon API URL
     * @var string
     */
    public $apiURL;
    
    /**
     * Mastodon Instance URL
     * @var string
     */
    public $domainURL;

    /**
     * Setting up domain and API URL
     * 
     * @param string $domain
     */
    public function __construct($domain)
    {
        $this->domainURL = 'https://' . $domain . '/';
        $this->apiURL = 'api/v1/';
    }

    /**
     * Do a POST http request and return response.
     * 
     * For URL, domain will be automatically added.
     * Array headers and params will be automatically encoded for request.
     * 
     * If response is JSON format, it will be decoded before return
     * 
     * @param string $url
     * @param array $headers
     * @param array $params
     * @return mixed
     */
    public function post($url, $headers = [], $params = [])
    {
        return $this->request(
            'POST', 
            $url, 
            $headers, 
            $params
        );
    }
    
    /**
     * Do a GET http request and return response.
     * 
     * For URL, domain will be automatically added.
     * Array headers and params will be automatically encoded for request.
     * 
     * If response is JSON format, it will be decoded before return
     * 
     * @param string $url
     * @param array $headers
     * @param array $params
     * @return mixed
     */
    public function get($url, $headers = [],$params = [])
    {
        return $this->request(
            'GET', 
            $url, 
            $headers,
            $params
        );
    }

    /**
     * Do a http request and return response.
     * 
     * For URL, domain will be automatically added.
     * Array headers and params will be automatically encoded for request.
     * 
     * If response is JSON format, it will be decoded before return
     * 
     * @param string $method POST or GET
     * @param string $url
     * @param array $headers
     * @param array $params
     * @return mixed
     */
    protected function request($method, $url, $headers = [], $params = [])
    {
        $curl = curl_init();

        curl_setopt_array($curl, $this->getOpts($method, $url, $headers, $params));

        $response = curl_exec($curl);

        if (curl_error($curl) !== '') {
            die( 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
        }
        curl_close($curl);
        
        if ($response !==  false) {
            $json = json_decode($response, true);
        }
        else {
            return false;
        }
        
        return ($json !== null) ? $json : $response ;
    }

    /**
     * Encode Parameters before HTTP request
     * 
     * @param array $params
     * @return string
     */
    protected function encodeParameters($params)
    {
        if (is_array($params) && count($params) > 0) {
            // Many parameters, encode them
            $paramsString = '';
            foreach ($params as $key => $value) {
                $paramsString .= '&' . urlencode($key) . '=' . urlencode($value);
            }
            // Remove first '&'
            return substr($paramsString, 1);
        } elseif ($params) {
            // return original
            return $params;
        }
    }

    /**
     * Encode Headers before HTTP request
     * 
     * @param array $headers
     * @return string
     */
    protected function encodeHeaders($headers)
    {
        if (is_array($headers) && count($headers) > 0) {
            // Many headers, encode them
            $headersString = '';
            foreach ($headers as $key => $value) {
                $headersString .= "{$key}: {$value}\r\n";
            }
            // Return trimmed string
            return trim($headersString);
        }
        return null;
    }

    /**
     * Get Options to create the cURL Opts
     * 
     * @param string $method POST or GET
     * @param string $url    URL
     * @param array $headers
     * @param array $params
     * @return array
     */
    protected function getOpts($method, $url, $headers, $params)
    {
        if (isset($headers['Authorization'])) {
            $params['access_token'] = str_replace('Bearer ', '', $headers['Authorization']);
        }
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . $this->encodeParameters($params);
            $opts = [];
        }
        else {
            $fields = is_array($params) ? http_build_query($params) : $params;
            $opts = [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $fields
            ];
        }
        $defaultsOpts = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_URL            => $this->domainURL . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 30
        ];

        return $defaultsOpts + $opts;
    }

}
