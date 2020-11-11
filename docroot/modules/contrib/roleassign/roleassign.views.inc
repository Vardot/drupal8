<?php

/**
 * @file
 * Provide views data for roleassign.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function roleassign_views_data_alter(&$data) {
  // Swap the default "user_bulk_form" Views field plugin with our own.
  $data['users']['user_bulk_form']['field']['id'] = 'roleassign_user_bulk_form';
}
