<?php

namespace Drupal\social_auth_linkedin\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings;
use League\OAuth2\Client\Provider\LinkedIn;

/**
 * Defines a Network Plugin for Social Auth LinkedIn.
 *
 * @package Drupal\social_auth_linkedin\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_linkedin",
 *   social_network = "LinkedIn",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings",
 *       "config_id": "social_auth_linkedin.settings"
 *     }
 *   }
 * )
 */
class LinkedInAuth extends NetworkBase implements LinkedInAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \League\OAuth2\Client\Provider\LinkedIn|false
   *   The initialized 3rd party library instance.
   *
   * @throws \Drupal\social_api\SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\League\OAuth2\Client\Provider\LinkedIn';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The LinkedIn library for PHP League OAuth2 not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => Url::fromRoute('social_auth_linkedin.callback')->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new LinkedIn($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings $settings
   *   The LinkedIn auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(LinkedInAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_linkedin')
        ->error('Define Client ID and Client Secret in module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
