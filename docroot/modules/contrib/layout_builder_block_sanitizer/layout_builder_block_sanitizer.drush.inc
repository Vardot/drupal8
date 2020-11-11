<?php

/**
 * @file
 * Layout Builder Block Sanitizer drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function layout_builder_block_sanitizer_drush_command() {

  $commands['lbbs-sanitize-node'] = [
    'description' => 'Sanitize nodes of invalid block content.',
    'aliases' => ['lbbssn'],
    'options' => [
      'cr' => 'Whether to rebuild cache prior to sanitization. Specify "true" or "false". Defaults to "true"',
      'node' => 'A specific node ID to sanitize.',
    ],
    'examples' => [
      'drush lbbssn' => 'Sanitize all nodes on site.',
      'drush lbbssn 3' => 'Sanitizes node with NID of 3.',
    ],
  ];

  return $commands;
}

/**
 * Drush command to sanitize a single node.
 *
 * Follows standard of drush_[MODULE_NAME]_[COMMAND_NAME]().
 */
function drush_layout_builder_block_sanitizer_lbbs_sanitize_node() {
  $clear_caches = drush_get_option('cr', "true");
  if ($clear_caches == "true") {
    drush_print(dt('Flushing all caches prior to sanitization..'));
    drupal_flush_all_caches();
  }
  $nid = drush_get_option('node', NULL);
  if ($nid == NULL) {
    \Drupal::service('layout_builder_block_sanitizer.batch')->batchSanitizeAllNodesStart();
  }
  else {
    drush_print(dt('Sanitizing node ID :nid of invalid blocks...', [':nid' => $nid]));
    \Drupal::service('layout_builder_block_sanitizer.manager')->sanitizeNode($nid);
  }

  drush_print(dt('Sanitization complete.'));
}
