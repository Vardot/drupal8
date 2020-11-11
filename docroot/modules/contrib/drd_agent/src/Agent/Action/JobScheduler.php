<?php

namespace Drupal\drd_agent\Agent\Action;



/**
 * Provides a 'JobScheduler' code.
 */
class JobScheduler extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!$this->moduleHandler->moduleExists('job_scheduler')) {
      job_scheduler_rebuild_all();
    }
    return [];
  }

}
