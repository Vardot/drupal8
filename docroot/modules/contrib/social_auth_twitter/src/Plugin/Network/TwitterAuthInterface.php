<?php

namespace Drupal\social_auth_twitter\Plugin\Network;

/**
 * Defines an interface for Twitter Auth Network Plugin.
 */
interface TwitterAuthInterface {

  /**
   * Gets the absolute url of the callback.
   *
   * @return string
   *   The callback url.
   */
  public function getOauthCallback();

  /**
   * Gets a TwitterOAuth instance with oauth_token and oauth_token_secret.
   *
   * This method creates the SDK object by also passing the oauth_token and
   * oauth_token_secret. It is used for getting permanent tokens from
   * Twitter and authenticating users that has already granted permission.
   *
   * @param string $oauth_token
   *   The oauth token.
   * @param string $oauth_token_secret
   *   The oauth token secret.
   *
   * @return \Abraham\TwitterOAuth\TwitterOAuth
   *   The instance of the connection to Twitter.
   */
  public function getSdk2($oauth_token, $oauth_token_secret);

}
