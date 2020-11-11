<?php

/**
 * @file
 * Post update functions for Menu Position.
 */

/**
 * Implements hook_post_update_NAME().
 */
function menu_position_post_update_clear_cache_for_active_trail() {
  // Trigger a cache rebuild for the new config.factory argument added to
  // MenuPositionServiceProvider.
}

/**
 * Implements hook_post_update_NAME().
 */
function menu_position_post_update_clear_rebuild_for_entity_query_removal() {
  // Trigger a cache rebuild for the removal entity.query argument since it's
  // deprecated in Drupal 9.
  // @see https://www.drupal.org/node/2849874
}
