<?php

namespace Drupal\social_auth_twitter\Settings;

use Drupal\social_auth\Settings\SettingsInterface;

/**
 * Defines an interface for Social Auth Twitter settings.
 */
interface TwitterAuthSettingsInterface extends SettingsInterface {

  /**
   * Gets the consumer key.
   *
   * @return string
   *   The consumer key.
   */
  public function getConsumerKey();

  /**
   * Gets the consumer secret.
   *
   * @return string
   *   The consumer secret.
   */
  public function getConsumerSecret();

}
