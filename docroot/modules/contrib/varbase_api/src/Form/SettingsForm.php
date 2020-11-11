<?php

namespace Drupal\varbase_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * SettingsForm Class Doc Comment.
 *
 * @category Class
 * @package Varbase
 */

/**
 * The settings form for controlling Content API's behavior.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['varbase_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'varbase_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('varbase_api.settings');

    $form['entity_json'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expose a "View JSON" link in entity operations'),
      '#default_value' => $config->get('entity_json'),
    ];
    $form['bundle_docs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expose a "View API Documentation" link in bundle entity operations'),
      '#default_value' => $config->get('bundle_docs'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('varbase_api.settings')
      ->set('entity_json', (bool) $form_state->getValue('entity_json'))
      ->set('bundle_docs', (bool) $form_state->getValue('bundle_docs'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
