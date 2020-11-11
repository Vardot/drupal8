<?php

namespace Drupal\update_helper\Utility;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Command helper for update helper.
 */
class CommandHelper implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Applying an (optional) update hook (function) from module install file.
   *
   * @param string $module
   *   Drupal module name.
   * @param string $update_hook
   *   Name of update_hook to apply.
   * @param bool $force
   *   Force the update.
   */
  public function applyUpdate($module = '', $update_hook = '', $force = FALSE) {
    if (!$update_hook || !$module) {
      $this->logger->error(dt('Please provide a module name and an update hook. Example: drush uhau <module> <update_hook>'));
      return;
    }

    $updateHelper = \Drupal::service('update_helper.updater');
    $updateHelper->executeUpdate($module, $update_hook, $force);
    return $updateHelper->logger()->output();
  }

}
