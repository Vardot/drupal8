<?php

/**
 * @file
 * Hooks for the Styleguide module.
 */

/**
 * Alter styleguide elements.
 *
 * @param array &$items
 *   An array of items to be displayed.
 *
 * @see hook_styleguide()
 */
function hook_styleguide_alter(array &$items) {
  // Add a class to the text test.
  $items['text']['content'] = '<div class="mytestclass">' . $items['text']['content'] . '</div>';
  // Remove the headings tests.
  unset($items['headings']);
}
