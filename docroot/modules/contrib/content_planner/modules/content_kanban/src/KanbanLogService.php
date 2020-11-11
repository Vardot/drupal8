<?php

namespace Drupal\content_kanban;

use Drupal\content_kanban\Entity\KanbanLog;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class KanbanLogService.
 */
class KanbanLogService {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new KanbanLogService object.
   */
  public function __construct(Connection $database, EntityTypeManager $entityTypeManager) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Creates a new log entry.
   *
   * @param string $name
   *   The name of the entry.
   * @param int $user_id
   *   The user id.
   * @param int $entity_id
   *   The entity id.
   * @param string $entity_type
   *   The entity type.
   * @param string $workflow_id
   *   The workflow id.
   * @param null|string $state_from
   *   The state_from value if it exists, NULL by default.
   * @param null|string $state_to
   *   The state_to value if it exists, NULL by default.
   *
   * @return int
   *   Returns the entity save response code for save action.
   */
  public function createLogEntity($name, $user_id, $entity_id, $entity_type, $workflow_id, $state_from = NULL, $state_to = NULL) {

    $entity_build = [
      'name' => $name,
      'user_id' => $user_id,
      'entity_id' => $entity_id,
      'entity_type' => $entity_type,
      'workflow_id' => $workflow_id,
      'state_from' => $state_from,
      'state_to' => $state_to,
    ];

    $entity = KanbanLog::create($entity_build);

    try {
      return $entity->save();
    }
    catch (EntityStorageException $e) {
      watchdog_exception('content_kanban', $e);
    }

    return 0;
  }

  /**
   * Get recent Logs.
   *
   * @param int $limit
   *   The limit of the queried logs.
   * @param array $filter
   *   An array containing filters if any.
   *
   * @return \Drupal\content_kanban\Entity\KanbanLog[]
   *   Returns an array of Kanban logs.
   */
  public function getRecentLogs($limit = 10, array $filter = []) {
    $query = $this->entityTypeManager->getStorage('content_kanban_log')->getQuery();
    $query->sort('created', 'DESC');
    $query->range(0, $limit);

    if (isset($filter['exclude_anonymous_users']) && $filter['exclude_anonymous_users'] == TRUE) {
      $query->condition('user_id', 0, '<>');
    }

    $result = $query->execute();

    if ($result) {
      return KanbanLog::loadMultiple($result);
    }

    return [];
  }

}
