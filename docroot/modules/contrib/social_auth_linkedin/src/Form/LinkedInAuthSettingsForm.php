<?php

namespace Drupal\social_auth_linkedin\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth LinkedIn.
 */
class LinkedInAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_linkedin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_linkedin.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_linkedin.settings');

    $form['linkedin_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('LinkedIn Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a LinkedIn App at <a href=":linkedin-dev">:linkedin-dev</a>',
        ['@linkedin-dev' => 'https://www.linkedin.com/secure/developer']),
    ];

    $form['linkedin_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Client ID here.'),
    ];

    $form['linkedin_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret here.'),
    ];

    $form['linkedin_settings']['authorized_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Authorized redirect URL'),
      '#description' => $this->t('Copy this value to <em>Authorized Redirect URLs</em> field of your LinkedIn App settings.'),
      '#default_value' => Url::fromRoute('social_auth_linkedin.callback')->setAbsolute()->toString(),
    ];

    $form['linkedin_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['linkedin_settings']['advanced']['scopes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Scopes for API call'),
      '#default_value' => $config->get('scopes'),
      '#description' => $this->t('Define any additional scopes to be requested, separated by a comma (e.g.: r_1st_connections,w_member_social).<br>
                                  The scopes \'r_liteprofile\' and \'r_emailaddress\' are added by default and always requested.<br>
                                  You can see the full list of valid fields and required scopes <a href="@fields">here</a>.',
                                  ['@fields' => 'https://docs.microsoft.com/en-us/linkedin/consumer/']),
    ];

    $form['linkedin_settings']['advanced']['endpoints'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API calls to be made to collect data'),
      '#default_value' => $config->get('endpoints'),
      '#description' => $this->t('Define the endpoints to be requested when user authenticates with LinkedIn for the first time<br>
                                  Enter each endpoint in different lines in the format <em>endpoint</em>|<em>name_of_endpoint</em>.<br>
                                  <b>For instance:</b><br>
                                  /v2/me|profile'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_linkedin.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->set('scopes', $values['scopes'])
      ->set('endpoints', $values['endpoints'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
