<?php

namespace Drupal\lb_ux_test\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;

/**
 * Provides a test layout that always adds a status message.
 *
 * @Layout(
 *   id = "lb_ux_test_form_no_validation",
 *   label = @Translation("LB UX form no validation"),
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main Region")
 *     }
 *   },
 * )
 */
class TestLayoutFormNoValidation extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->messenger()->addStatus('Check 1 2');
    return parent::buildConfigurationForm($form, $form_state);
  }

}
