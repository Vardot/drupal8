<?php

namespace Drupal\drd_agent\Agent\Action;



/**
 * Provides a 'Cron' code.
 */
class Cron extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    /** @noinspection NullPointerExceptionInspection */
    $this->container->get('cron')->run();
    return [];
  }

}
