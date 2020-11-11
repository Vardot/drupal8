<?php

namespace Drupal\drd_agent\Agent\Auth;



/**
 * Implements the UsernamePassword authentication method.
 */
class UsernamePassword extends Base {

  /**
   * {@inheritdoc}
   */
  public function validate(array $settings): bool {
    if ($this->currentUser->isAuthenticated()) {
      return TRUE;
    }
    return $this->userAuth->authenticate($settings['username'], $settings['password']);
  }

}
