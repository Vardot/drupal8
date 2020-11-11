<?php

namespace Drupal\menu_position;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Example entity.
 */
interface MenuPositionRuleInterface extends ConfigEntityInterface {

  /**
   * Returns the ID of the menu position rule.
   *
   * @return int
   *   The unique identifier of the menu position rule
   */
  public function getId();

  /**
   * Returns the administrative title of the menu position rule.
   *
   * @return string
   *   The administrative title of the menu position rule.
   */
  public function getLabel();

  /**
   * Returns the status of the menu position rule.
   *
   * @return bool
   *   The status of the menu position rule.
   */
  public function getEnabled();

  /**
   * Returns the content type conditions.
   *
   * @return array
   *   The array of configuration for content types.
   */
  public function getConditions();

  /**
   * Returns the menu item.
   *
   * @return string
   *   The menu item.
   */
  public function getMenuLink();

  /**
   * Returns the menu name.
   *
   * @return string
   *   The menu name.
   */
  public function getMenuName();

  /**
   * Returns the parent menu item.
   *
   * @return string
   *   The parent menu item.
   */
  public function getParent();

  /**
   * Returns weight for the particular menu position rule.
   *
   * @return int
   *   Weight for the particular rule.
   */
  public function getWeight();

  /**
   * Sets the administrative title of the menu position rule.
   *
   * @param string $label
   *   The administrative title of the menu position rule.
   */
  public function setLabel($label);

  /**
   * Sets the status menu position rule.
   *
   * @param bool $enabled
   *   The status of the menu position rule.
   */
  public function setEnabled($enabled);

  /**
   * Sets the configuration options for the menu position rules.
   *
   * @param array $conditions
   *   An array of $conditions.
   * @param string $plugin
   *   The machine plugin name.
   */
  public function setConditions(array $conditions, $plugin);

  /**
   * Sets the menu item.
   *
   * @return string
   *   The menu link id.
   */
  public function setMenuLink($menu_link);

  /**
   * Sets the menu name.
   *
   * @return string
   *   The menu name.
   */
  public function setMenuName($menu_name);

  /**
   * Sets the parent link id.
   *
   * @return string
   *   The parent menu link id.
   */
  public function setParent($parent);

  /**
   * Sets weight for the particular menu position rule.
   *
   * @param int $weight
   *   Weight for the particular rule.
   */
  public function setWeight($weight);

}
