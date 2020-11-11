<?php

namespace Drupal\drd_agent\Agent\Action;

use Exception;

/**
 * Provides a 'Php' code.
 */
class Php extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $args = $this->getArguments();
    try {
      if (!empty($args['php'])) {
        $filename = 'temporary://drd_agent_php.inc';
        file_put_contents($filename, $args['php']);
        /** @noinspection PhpIncludeInspection */
        require_once $filename;
        unlink($filename);
      }
    }
    catch (Exception $ex) {
      $this->messenger->addMessage(t('Error while executing PHP: :msg', [
        ':msg' => $ex->getMessage(),
      ]), 'error');
    }
    return [];
  }

}
