<?php
require_once 'HttpRequest.php';
require_once 'Toot.php';

class MastodonClient {

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
  public function __construct($domain, $token) {
    $this->domain = $domain;

    $this->http = new HttpRequest($this->domain);

    $this->appCredentials['bearer'] = $token;
    $this->headers['Authorization'] = $token;
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
  public function postStatus (Toot $toot) {
    $body = [
      'visibility' => 'public'
    ];

    if ($toot->hasContentWarning()) {
      $body = array_merge($body, [
        'status' => $toot->getContentWarningText(),
        'spoiler_text' => $toot->getMainText()
      ]);
    } else {
      $body['status'] = $toot->getFullText();
    }

    return $this->http->post(
      $this->http->apiURL . 'statuses',
      $this->headers,
      $body
    );
  }
}
