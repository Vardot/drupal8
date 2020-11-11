<?php

namespace Drupal\drd_agent\Agent\Action;

use Drupal;
use Drupal\Core\Site\Settings;
use Drupal\drd_agent\Agent\Remote\Monitoring;
use Drupal\drd_agent\Agent\Remote\SecurityReview;

/**
 * Provides a 'Info' code.
 */
class Info extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $config = $this->configFactory->get('system.site');
    // Initial set of information.
    $result = [
      'root' => DRUPAL_ROOT,
      'version' => Drupal::VERSION,
      'name' => $config->get('name'),
      'globals' => [],
      'settings' => Settings::getAll(),
      'review' => SecurityReview::create($this->container)->collect(),
      'monitoring' => Monitoring::create($this->container)->collect(),
    ];

    // Check run-time requirements and status information.
    if ($systemManager = $this->container->get('system.manager')) {
      $result['requirements'] = $systemManager->listRequirements();
    }

    $result['variables'] = $GLOBALS['config'];
    foreach ($GLOBALS as $key => $value) {
      if (!in_array($key, [
        'config',
        'GLOBALS',
        'autoloader',
        'kernel',
        'request',
      ])) {
        $result['globals'][$key] = $value;
      }
    }

    return $result;
  }

}
