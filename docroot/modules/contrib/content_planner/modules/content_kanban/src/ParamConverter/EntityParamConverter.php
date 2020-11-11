<?php

namespace Drupal\content_kanban\ParamConverter;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Class EntityParamConverter.
 */
class EntityParamConverter implements ParamConverterInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityParamConverter constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    try {
      $entityType = $this->entityTypeManager->getStorage($defaults['entity_type']);
      $entity = $entityType->load($value);
      return $entity;
    }
    catch (InvalidPluginDefinitionException $e) {
      watchdog_exception('content_kanban', $e);
    }
    catch (PluginNotFoundException $e) {
      watchdog_exception('content_kanban', $e);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] == 'entity';
  }

}
