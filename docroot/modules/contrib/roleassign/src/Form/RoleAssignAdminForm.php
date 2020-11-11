<?php

namespace Drupal\roleassign\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * Configure book settings for this site.
 */
class RoleAssignAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_assign_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['roleassign.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all available roles except for
    // 'anonymous user' and 'authenticated user'.
    $roles = user_role_names(TRUE);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);

    // Show checkboxes with roles that can be delegated if any.
    if ($roles) {
      $config = $this->config('roleassign.settings');
      $form['roleassign_roles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Roles'),
        '#default_value' => $config->get('roleassign_roles'),
        '#options' => $roles,
        '#description' => $this->t('Select roles that should be available for assignment.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('roleassign.settings')
      ->set('roleassign_roles', $form_state->getValue('roleassign_roles'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
