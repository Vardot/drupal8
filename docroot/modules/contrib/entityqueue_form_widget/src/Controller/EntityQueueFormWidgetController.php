<?php

namespace Drupal\entityqueue_form_widget\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Module controller.
 */
class EntityQueueFormWidgetController extends ControllerBase {

  /**
   * Show module home page content.
   */
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Entity Queue Form Widget Custom Page'),
    ];
  }

}
