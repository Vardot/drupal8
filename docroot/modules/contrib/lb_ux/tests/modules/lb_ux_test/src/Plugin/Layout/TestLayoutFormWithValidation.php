<?php

namespace Drupal\lb_ux_test\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;

/**
 * Provides a test layout that always adds a status message.
 *
 * @Layout(
 *   id = "lb_ux_test_form_with_validation",
 *   label = @Translation("LB UX form with validation"),
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main Region")
 *     }
 *   },
 * )
 */
class TestLayoutFormWithValidation extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if ($form_state->getValue('label') !== 'Abracadabra') {
      $form_state->setErrorByName('label', "That's not the magic word!");
    }
  }

}
