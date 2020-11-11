<?php

/**
 * @file
 * Hooks related to genpass module and password generation.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Generate a password of a given length and retur it.
 *
 * @param integer
 *   The length of the password to return.
 *
 * @see user_password()
 */
function hook_password($length) {
  // Generate a password using our method of $length.
  return genpass_password($length);
}

/**
 * Alter the character sets used in genpass_password().
 *
 * @param array $character_sets
 *   A array of strings which make up separate character sets.
 *
 * @throws \Drupal\genpass\InvalidCharacterSetsException.
 *   In the event that the character set is too small to be used. Minimum size
 *   is the length of the password.
 */
function hook_genpass_character_sets_alter(&$character_sets) {

  // Add the similar characters back in to annoy users.
  $character_sets['annoyingly_similar'] .= '`|I1l0O';
}

/**
 * @} End of "addtogroup hooks".
 */
