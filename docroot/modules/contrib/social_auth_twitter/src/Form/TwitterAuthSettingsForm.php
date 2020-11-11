<?php

namespace Drupal\social_auth_twitter\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth Twitter.
 */
class TwitterAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_twitter_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_twitter.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_twitter.settings');

    $form['twitter_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter OAuth Settings'),
      '#open' => TRUE,
    ];

    $form['twitter_settings']['consumer_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Consumer Key'),
      '#default_value' => $config->get('consumer_key'),
      '#description' => $this->t('Copy the Consumer Key here'),
    ];

    $form['twitter_settings']['consumer_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Consumer Secret'),
      '#default_value' => $config->get('consumer_secret'),
      '#description' => $this->t('Copy the Consumer Secret here'),
    ];

    $form['twitter_settings']['callback_urls'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Callback URLs'),
      '#description' => $this->t('Copy this value to <em>Callback URLs</em> field of your Twitter App settings.'),
      '#default_value' => Url::fromRoute('social_auth_twitter.callback')->setAbsolute()->toString(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('social_auth_twitter.settings')
      ->set('consumer_key', trim($values['consumer_key']))
      ->set('consumer_secret', trim($values['consumer_secret']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
