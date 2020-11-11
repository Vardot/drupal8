<?php

namespace Drupal\drd_agent\Agent\Action;



/**
 * Provides a 'MaintenanceMode' code.
 */
class MaintenanceMode extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $args = $this->getArguments();
    if ($args['mode'] === 'getStatus') {
      return [
        'data' => $this->state->get('system.maintenance_mode'),
      ];
    }

    $this->state->set('system.maintenance_mode', ($args['mode'] === 'on'));
    drupal_flush_all_caches();
    return [];
  }

}
