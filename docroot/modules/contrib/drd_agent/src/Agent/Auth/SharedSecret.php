<?php

namespace Drupal\drd_agent\Agent\Auth;

/**
 * Implements the SharedSecret authentication method.
 */
class SharedSecret extends Base {

  /**
   * {@inheritdoc}
   */
  public function validate(array $settings): bool {
    return ($settings['secret'] === $this->storedSettings['secret']);
  }

}
