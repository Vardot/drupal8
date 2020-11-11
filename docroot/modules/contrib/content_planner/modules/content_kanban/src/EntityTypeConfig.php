<?php

namespace Drupal\content_kanban;

/**
 * Class EntityTypeConfig.
 */
class EntityTypeConfig {

  /**
   * The bundle of the entity type.
   *
   * @var string
   */
  public $id = '';

  /**
   * The label of the entity type bundle.
   *
   * @var string
   */
  public $label = '';

  /**
   * The color that will be used for the bundle.
   *
   * @var string
   */
  public $color = '';

  /**
   * The entity type (e.g. node etc.)
   *
   * @var string
   */
  protected $entityType = '';

  /**
   * EntityTypeConfig constructor.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The bundle of the entity type.
   * @param string $label
   *   The label.
   * @param string $color
   *   The color.
   */
  public function __construct($entityType, $bundle, $label, $color) {
    $this->entityType = $entityType;
    $this->id = $bundle;
    $this->label = $label;
    $this->color = $color;
  }

  /**
   * Sets the color.
   *
   * @param string $value
   *   The color that will be used.
   */
  public function setColor($value) {
    $this->color = $value;
  }

  /**
   * Gets the entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Get Label.
   *
   * @return string
   *   Returns a string representing the entity type config label.
   */
  public function getLabel() {
    return $this->label;
  }

}
