<?php

namespace Drupal\drd_agent\Agent\Action;

/**
 * Provides a 'FlushCache' code.
 */
class FlushCache extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    drupal_flush_all_caches();
    return [
      'data' => 'cache flushed',
    ];
  }

}
