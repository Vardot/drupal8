<?php

namespace Drupal\charts\Settings;

/**
 * Class ChartsDefaultColors.
 *
 * @package Drupal\charts\Settings
 */
class ChartsDefaultColors {

  /**
   * Default colors.
   *
   * @var array
   */
  protected $defaultColors = [
    '#2f7ed8',
    '#0d233a',
    '#8bbc21',
    '#910000',
    '#1aadce',
    '#492970',
    '#f28f43',
    '#77a1e5',
    '#c42525',
    '#a6c96a',
  ];

  /**
   * Default defined colors.
   *
   * @return array
   *   return default colors.
   */
  public function getDefaultColors() {
    return $this->defaultColors;
  }

  /**
   * Define default colors.
   *
   * @param array $defaultColors
   *   Default colors.
   */
  public function setDefaultColors(array $defaultColors) {
    $this->defaultColors = $defaultColors;
  }

  /**
   * Provide a random color.
   *
   * @return string
   *   A random color.
   */
  public static function randomColor() {
    return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
  }

  // Private static function randomColorPart() {.
  // Return str_pad( dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
  // }.
}
