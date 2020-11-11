<?php

namespace Drupal\entity_usage\Commands;

use Drush\Commands\DrushCommands;
use Drupal\entity_usage\EntityUsageBatchManager;

/**
 * Entity Usage drush commands.
 */
class EntityUsageCommands extends DrushCommands {

  /**
   * The Entity Usage batch manager.
   *
   * @var \Drupal\entity_usage\EntityUsageBatchManager
   */
  protected $batchManager;

  /**
   * Creates a EntityUsageCommands object.
   *
   * @param \Drupal\entity_usage\EntityUsageBatchManager $batch_manager
   *   The entity usage batch manager.
   */
  public function __construct(EntityUsageBatchManager $batch_manager) {
    parent::__construct();
    $this->batchManager = $batch_manager;
  }

  /**
   * Recreate all entity usage statistics.
   *
   * @command entity-usage:recreate
   * @aliases eu-r,entity-usage-recreate
   */
  public function recreate() {
    $this->batchManager->recreate();
    drush_backend_batch_process();
  }

}
