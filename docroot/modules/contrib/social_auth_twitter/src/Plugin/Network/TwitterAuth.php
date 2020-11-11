<?php

namespace Drupal\social_auth_twitter\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\social_auth_twitter\Settings\TwitterAuthSettingsInterface;

/**
 * Defines Social Auth Twitter Network Plugin.
 *
 * @Network(
 *   id = "social_auth_twitter",
 *   social_network = "Twitter",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_twitter\Settings\TwitterAuthSettings",
 *       "config_id": "social_auth_twitter.settings"
 *     }
 *   }
 * )
 */
class TwitterAuth extends NetworkBase implements TwitterAuthInterface {

  /**
   * {@inheritdoc}
   */
  public function initSdk() {
    $class_name = '\Abraham\TwitterOAuth\TwitterOAuth';

    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Twitter Client could not be found. Class: %s.', $class_name));
    }

    /** @var \Drupal\social_auth_twitter\Settings\TwitterAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // Creates a and sets data to TwitterOAuth object.
      $client = new TwitterOAuth($settings->getConsumerKey(), $settings->getConsumerSecret());

      $proxy = $this->getProxy();
      if ($proxy) {
        $client->setProxy($proxy);
      }

      return $client;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOauthCallback() {
    return Url::fromRoute('social_auth_twitter.callback')->setAbsolute()->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getSdk2($oauth_token, $oauth_token_secret) {
    /** @var \Drupal\social_auth_twitter\Settings\TwitterAuthSettings $settings */
    $settings = $this->settings;

    $client = new TwitterOAuth($settings->getConsumerKey(), $settings->getConsumerSecret(),
                $oauth_token, $oauth_token_secret);

    $proxy = $this->getProxy();
    if ($proxy) {
      $client->setProxy($proxy);
    }

    return $client;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_twitter\Settings\TwitterAuthSettingsInterface $settings
   *   The Twitter auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(TwitterAuthSettingsInterface $settings) {
    $consumer_key = $settings->getConsumerKey();
    $consumer_secret = $settings->getConsumerSecret();
    if (!$consumer_key || !$consumer_secret) {
      $this->loggerFactory
        ->get('social_auth_twitter')
        ->error('Define Consumer Key and Consumer Secret in module settings.');

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Parse proxy settings.
   *
   * @return array
   *   The proxy settings or NULL if not set.
   */
  private function getProxy() {
    $proxy = NULL;

    $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['https'];

    if ($proxyUrl) {
      $proxy_settings = parse_url($proxyUrl);

      if ($proxy_settings) {
        $proxy = [
          'CURLOPT_PROXY' => $proxy_settings['host'],
          'CURLOPT_PROXYUSERPWD' => "{$proxy_settings['user']}:{$proxy_settings['pass']}",
          'CURLOPT_PROXYPORT' => $proxy_settings['port'],
        ];
      }
    }

    return $proxy;
  }

}
