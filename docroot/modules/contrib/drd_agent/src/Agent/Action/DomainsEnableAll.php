<?php

namespace Drupal\drd_agent\Agent\Action;

/**
 * Provides a 'DomainsEnableAll' code.
 */
class DomainsEnableAll extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $args = $this->getArguments();
    $result = [];

    $drush = $drupalconsole = FALSE;
    if (!empty($args['drush'])) {
      exec($args['drush'] . ' --version', $output, $ret);
      $drush = ($ret === 0);
    }
    if (!empty($args['drupalconsole'])) {
      exec($args['drupalconsole'] . ' --version', $output, $ret);
      $drupalconsole = ($ret === 0);
    }

    if ($drush || $drupalconsole) {
      foreach ($args['urls'] as $url => $token) {
        $success = FALSE;
        if ($drush) {
          exec($args['drush'] . ' -y --uri=' . $url . ' --root=' . DRUPAL_ROOT . ' pm-enable drd_agent', $output, $ret);
          if ($ret === 0) {
            exec($args['drush'] . ' -y --uri=' . $url . ' --root=' . DRUPAL_ROOT . ' drd-agent-setup ' . $token, $output, $ret1);
            if ($ret1 === 0) {
              $success = TRUE;
            }
          }
        }
        if (!$success && $drupalconsole) {
          exec($args['drupalconsole'] . ' module:install -y --uri=' . $url . ' --root=' . DRUPAL_ROOT . ' drd_agent', $output, $ret);
          if ($ret === 0) {
            exec($args['drupalconsole'] . ' -y --uri=' . $url . ' --root=' . DRUPAL_ROOT . ' drd:agent:setup ' . $token, $output, $ret1);
            if ($ret1 === 0) {
              $success = TRUE;
            }
          }
        }
        if ($success) {
          $result[] = $url;
        }
      }
    }

    return $result;
  }

}
