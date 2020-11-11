<?php

namespace Drupal\drd_agent\Agent\Action;

/**
 * Provides a 'Ping' code.
 */
class Ping extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return [
      'data' => 'pong',
    ];
  }

}
