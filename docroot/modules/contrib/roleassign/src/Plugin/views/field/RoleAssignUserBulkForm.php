<?php

namespace Drupal\roleassign\Plugin\views\field;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\user\Plugin\views\field\UserBulkForm;

/**
 * Defines a user operations bulk form element, with RoleAssign logic applied.
 *
 * @ViewsField("roleassign_user_bulk_form")
 */
class RoleAssignUserBulkForm extends UserBulkForm {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    if (roleassign_restrict_access()) {
      // Remove actions that are not allowed based on RoleAssign settings.
      $assignable_roles = array_filter(\Drupal::config('roleassign.settings')->get('roleassign_roles'));
      foreach ($this->actions as $action_key => $action) {
        if (in_array($action->get('plugin'), ['user_add_role_action', 'user_remove_role_action'])) {
          $config = $action->get('configuration');
          if (!in_array($config['rid'], $assignable_roles)) {
            unset($this->actions[$action_key]);
          }
        }
      }
    }
  }
}
