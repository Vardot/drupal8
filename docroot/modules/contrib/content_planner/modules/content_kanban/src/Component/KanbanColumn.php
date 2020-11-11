<?php

namespace Drupal\content_kanban\Component;

/**
 * Class KanbanColumn.
 */
class KanbanColumn {

  /**
   * The workflow ID.
   *
   * @var string
   */
  protected $workflowID;

  /**
   * The state ID.
   *
   * @var string
   */
  protected $stateID;

  /**
   * The state info.
   *
   * @var array
   */
  protected $stateInfo = [];

  /**
   * An array containing the entities.
   *
   * @var array
   */
  protected $entities = [];

  /**
   * An array with the entity type configs.
   *
   * @var array|\Drupal\content_kanban\EntityTypeConfig[]
   */
  protected $entityTypeConfigs = [];

  /**
   * Constructor.
   *
   * @param string $workflow_id
   *   The workflow ID.
   * @param string $state_id
   *   The state ID.
   * @param array $state_info
   *   The state info.
   * @param array $entities
   *   An array with the entities.
   * @param \Drupal\content_kanban\EntityTypeConfig[] $entity_type_configs
   *   An array with the entity type configs objects.
   */
  public function __construct(
    $workflow_id,
    $state_id,
    array $state_info,
    array $entities,
    array $entity_type_configs
  ) {

    $this->workflowID = $workflow_id;
    $this->stateID = $state_id;
    $this->stateInfo = $state_info;
    $this->entities = $entities;
    $this->entityTypeConfigs = $entity_type_configs;
  }

  /**
   * Builds a Kanban Column.
   *
   * @return array
   *   Returns a renderable array for the current Kanban column.
   */
  public function build() {
    // Change here too the array structure.
    $entity_builds = [];
    // I set directly the type on the entity.
    foreach ($this->entities as $entity) {
      $kanbanEntry = new KanbanEntry(
        $entity,
        $this->stateID,
        $this->entityTypeConfigs[$entity->type]
      );
      $entity_builds[] = $kanbanEntry->build();
    }

    $build = [
      '#theme' => 'content_kanban_column',
      '#column_id' => $this->workflowID . '-' . $this->stateID,
      '#workflow_id' => $this->workflowID,
      '#state_id' => $this->stateID,
      '#state_label' => $this->stateInfo['label'],
      '#entities' => $entity_builds,
    ];

    return $build;
  }

}
