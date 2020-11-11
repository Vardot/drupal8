<?php

namespace Drupal\content_kanban;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Class KanbanWorkflowService.
 */
class KanbanWorkflowService {

  use StringTranslationTrait;
  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * The Kanban Log service.
   *
   * @var \Drupal\content_kanban\KanbanLogService
   */
  protected $kanbanLogService;

  /**
   * Constructs a new NewsService object.
   */
  public function __construct(
    Connection $database,
    ModerationInformationInterface $moderation_information,
    KanbanLogService $kanban_log_service
  ) {
    $this->database = $database;
    $this->moderationInformation = $moderation_information;
    $this->kanbanLogService = $kanban_log_service;
  }

  /**
   * Acts upon a entity presave.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The current entity that is saved.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user that is related to the entity save.
   *
   * @see content_kanban_entity_presave()
   */
  public function onEntityPresave(ContentEntityInterface $entity, AccountInterface $user) {
    // If the entity is moderated, meaning it belongs to a certain workflow.
    if ($this->moderationInformation->isModeratedEntity($entity)) {

      $current_state = $this->getCurrentStateId($entity);

      $prev_state = $this->getPreviousWorkflowStateId($entity);

      if ($current_state && $prev_state) {

        // Generate name for entity.
        $name = $this->t('Workflow State change on Entity')->render();

        // Get workflow from moderated entity.
        $workflow = $this->moderationInformation->getWorkflowForEntity($entity);

        // Create new log entity.
        $this->kanbanLogService->createLogEntity(
          $name,
          $user->id(),
          $entity->id(),
          $entity->getEntityTypeId(),
          $workflow->id(),
          $prev_state,
          $current_state
        );

      }

    }

  }

  /**
   * Gets the current State ID.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return mixed
   *   Returns the current moderation state id for the given entity.
   */
  public function getCurrentStateId(ContentEntityInterface $entity) {
    return $entity->moderation_state->value;
  }

  /**
   * Gets the label of the current state of a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return bool|string
   *   Returns the current state if any, FALSE otherwise.
   */
  public function getCurrentStateLabel(ContentEntityInterface $entity) {

    if ($this->moderationInformation->isModeratedEntity($entity)) {

      if ($workflow = $this->moderationInformation->getWorkflowForEntity($entity)) {

        if ($states = self::getWorkflowStates($workflow)) {

          $entity_workflow_state = $this->getCurrentStateId($entity);

          if (array_key_exists($entity_workflow_state, $states)) {
            return $states[$entity_workflow_state];
          }
        }
      }

    }

    return FALSE;
  }

  /**
   * Get Workflow States.
   *
   * @param \Drupal\workflows\Entity\Workflow $workflow
   *   The workflow object.
   *
   * @return array
   *   Returns an array with the available workflow states.
   */
  public static function getWorkflowStates(Workflow $workflow) {

    $states = [];

    $type_settings = $workflow->get('type_settings');

    // Sort by weight.
    uasort($type_settings['states'], function ($a, $b) {

      if ($a['weight'] == $b['weight']) {
        return 0;
      }
      elseif ($a['weight'] < $b['weight']) {
        return -1;
      }
      else {
        return 1;
      }

    });

    foreach ($type_settings['states'] as $state_id => $state) {
      $states[$state_id] = $state['label'];
    }

    return $states;
  }

  /**
   * Get ID of the previous workflow state.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return string
   *   Returns the previous state id.
   */
  public function getPreviousWorkflowStateId(ContentEntityInterface $entity) {

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);

    if ($state_history = $this->getWorkflowStateHistory($workflow->id(), $entity)) {

      if (isset($state_history[0])) {
        return $state_history[0];
      }
    }
    $state = $workflow->getTypePlugin()->getInitialState($entity);
    return $state->id();
  }

  /**
   * Gets the workflow state history of a given entity.
   *
   * @param string $workflow_id
   *   A string representing the workflow id.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which the workflow history is requested.
   *
   * @return array
   *   An array with the workflow state history for the given entity.
   */
  public function getWorkflowStateHistory($workflow_id, ContentEntityInterface $entity) {

    $query = $this->database->select('content_moderation_state_field_revision', 'r');

    $query->addField('r', 'moderation_state');

    $query->condition('r.workflow', $workflow_id);
    $query->condition('r.content_entity_type_id', $entity->getEntityTypeId());
    $query->condition('r.content_entity_id', $entity->id());

    $query->orderBy('r.revision_id', 'DESC');

    $result = $query->execute()->fetchAll();

    $return = [];

    foreach ($result as $row) {
      $return[] = $row->moderation_state;
    }

    return $return;
  }

}
