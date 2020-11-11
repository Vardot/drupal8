<?php

namespace Drupal\content_kanban;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Kanban Log entities.
 *
 * @ingroup content_kanban
 */
class KanbanLogListBuilder extends EntityListBuilder {

  /**
   * Custom load of entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Returns an array with the entities.
   */
  public function load() {

    $query = \Drupal::entityQuery('content_kanban_log');
    $query->sort('created', 'DESC');

    $result = $query->execute();
    return $this->storage->loadMultiple($result);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['id'] = $this->t('Kanban Log ID');
    $header['name'] = $this->t('Name');
    $header['workflow'] = $this->t('Workflow');
    $header['entity'] = $this->t('Entity / Entity ID');
    $header['state_from'] = $this->t('State from');
    $header['state_to'] = $this->t('State to');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    /* @var $entity \Drupal\content_kanban\Entity\KanbanLog */

    $row['id'] = $entity->id();
    $row['name'] = $entity->label();

    // Workflow.
    if ($workflow = $entity->getWorkflow()) {
      $row['workflow'] = $workflow->label();
    }
    else {
      $row['workflow'] = t('Workflow with ID @id does not exist anymore', ['@id' => $entity->getWorkflowId()]);
    }

    if ($logEntity = $entity->getEntityObject()) {
      $row['entity'] = new Link($logEntity->label(), $logEntity->toUrl());
    }
    else {
      $row['entity'] = t('Entity @entity_type with ID @id does not exist anymore', ['@id' => $entity->getEntityId(), '@entity_type' => $entity->getType()]);
    }

    $row['state_from'] = $entity->getStateFrom();
    $row['state_to'] = $entity->getStateTo();

    return $row + parent::buildRow($entity);
  }

}
