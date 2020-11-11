<?php

namespace Drupal\content_kanban;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\content_calendar\ContentTypeConfigService;
use Drupal\content_kanban\Form\SettingsForm;
use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\content_kanban\Form\KanbanFilterForm;

/**
 * Class KanbanService.
 */
class KanbanService {

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\content_moderation\ModerationInformationInterface definition.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * An array with the defined colors.
   *
   * @var array
   */
  protected $definedColors = [
  // Drupal Standard color.
    '#0074bd',
    '#D66611',
    '#27E834',
    '#FF3D2A',
    'purple',
    '#22FFA0',
    'black',
    '#37C2FF',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config content service.
   *
   * @var \Drupal\content_calendar\ContentTypeConfigService
   */
  protected $contentTypConfigService;

  /**
   * Constructs a new KanbanService object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    Connection $database,
    ModerationInformationInterface $moderation_information,
    EntityTypeManager $entityTypeManager,
    ModuleHandlerInterface $moduleHandler,
    ContentTypeConfigService $contentTypConfigService
  ) {
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->moderationInformation = $moderation_information;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->contentTypConfigService = $contentTypConfigService;
  }

  /**
   * Check if the Content Calendar module is enabled.
   *
   * @return bool
   *   Returns TRUE if the content_calendar module is enabled, FALSE otherwise.
   */
  public function contentCalendarModuleIsEnabled() {
    return $this->moduleHandler->moduleExists('content_planner');
  }

  /**
   * Gets the Kanban settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Returns the Kanban settings immutable config.
   */
  public function getKanbanSettings() {
    $settings = $this->configFactory->get(SettingsForm::$configName);

    return $settings;
  }

  /**
   * Checks if the option to use the Content Calendar colors is active.
   *
   * @return bool
   *   Returns TRUE if the use of calendar colors is enabled, FALSE otherwise.
   */
  public function useContentCalendarColors() {

    if ($this->contentCalendarModuleIsEnabled()) {
      $settings = $this->getKanbanSettings();

      if ($value = $settings->get('use_content_calendar_colors')) {
        return (bool) $value;
      }
    }

    return FALSE;
  }

  /**
   * Get Entity Type Configs.
   *
   * @param array $entityTypes
   *   An array with the available entity types and their bundles.
   *
   * @return \Drupal\content_kanban\EntityTypeConfig[]
   *   Returns an array with the entity type configs.
   */
  public function getEntityTypeConfigs(array $entityTypes = []) {
    $entityTypeConfigs = [];

    $color_index = 0;
    foreach ($entityTypes as $entityTypeId => $entityBundles) {
      foreach ($entityBundles as $bundle) {
        $entityTypeConfigs[$bundle] = new EntityTypeConfig(
          $entityTypeId,
          $bundle,
          ucfirst($bundle),
          $this->getColor($color_index)
        );
        $color_index++;
      }
    }

    // Override defined colors with colors from Content Calendar.
    if ($this->useContentCalendarColors()) {
      // Load Content Type configs from Content Calendar.
      $content_type_configs = $this->contentTypConfigService->loadAllEntities();
      // Overwrite colors.
      foreach ($content_type_configs as $content_type => $content_type_config) {
        if (array_key_exists($content_type, $entityTypeConfigs)) {
          $entityTypeConfigs[$content_type]->setColor($content_type_config->getColor());
        }
      }
    }

    return $entityTypeConfigs;
  }

  /**
   * Gets the Color.
   *
   * @param int $index
   *   The index associated with the requested color.
   *
   * @return string
   *   Returns the color id for the given index.
   */
  protected function getColor($index) {

    // If the desired index is greater than the count of defined colors.
    if (($index + 1) > count($this->definedColors)) {
      $index = 0;
    }

    return $this->definedColors[$index];
  }

  /**
   * Gets the Content Moderation Entities.
   *
   * @param string $workflow
   *   The workflow ID.
   * @param array $filters
   *   An array with the filters.
   * @param array $entities
   *   An array with the entities.
   *
   * @return \Drupal\content_moderation\Entity\ContentModerationState[]
   *   Returns an array with the content moderation states for the given
   *   workflow.
   */
  public function getEntityContentModerationEntities($workflow, array $filters = [], array $entities = []) {
    $result = [];
    try {
      $query = $this->entityTypeManager->getStorage('content_moderation_state')->getQuery();
      if (!empty(array_keys($entities))) {
        $query->condition('workflow', $workflow);
        $query->condition('content_entity_type_id', array_keys($entities), 'in');
      }

      // Moderation state filter.
      if (array_key_exists('moderation_state', $filters) && $filters['moderation_state']) {
        $query->condition('moderation_state', $filters['moderation_state']);
      }

      // User ID filter.
      if (array_key_exists('uid', $filters) && $filters['uid']) {
        $query->condition('uid', $filters['uid']);
      }

      // User ID filter.
      $result = $query->execute();
    }
    catch (InvalidPluginDefinitionException $e) {
      watchdog_exception('content_kanban', $e);
    }
    catch (PluginNotFoundException $e) {
      watchdog_exception('content_kanban', $e);
    }
    if ($result) {
      return ContentModerationState::loadMultiple($result);
    }

    return $result;
  }

  /**
   * Gets the entity IDs from Content Moderation entities.
   *
   * @param string $workflow
   *   The workflow id.
   * @param array $filters
   *   An array with the filters.
   * @param array $entities
   *   An array with the entities.
   *
   * @return array
   *   Returns an array with the entity ids.
   */
  public function getEntityIdsFromContentModerationEntities($workflow, array $filters = [], array $entities = []) {
    $entityIds = [];

    if ($content_moderation_states = $this->getEntityContentModerationEntities($workflow, $filters, $entities)) {
      foreach ($content_moderation_states as $content_moderation_state) {

        // Get property.
        $content_entity_id_property = $content_moderation_state->content_entity_id;

        // Get value.
        $content_entity_id_value = $content_entity_id_property->getValue();
        $entity_type_id_value = $content_moderation_state->get('content_entity_type_id')->getValue();
        // Get the entity type id.
        $entity_type_id = $entity_type_id_value[0]['value'];
        // Build the ids array with entity type as key.
        $entityIds[$entity_type_id][] = $content_entity_id_value[0]['value'];
      }

    }
    return $entityIds;
  }

  /**
   * Gets the entities by Type.
   *
   * @param array $entityIds
   *   An array with the entity ids.
   *
   * @return array
   *   Returns a array with the entities for the given entity ids.
   */
  public function getEntitiesByEntityIds(array $entityIds = [], $filters = []) {

    $result = [];
    // Basic table.
    if (!empty($entityIds)) {
      $query = [];
      // Build the query dynamically for all entities.
      foreach ($entityIds as $entityTypeName => $entityId) {
        try {
          $entityStorage = $this->entityTypeManager->getStorage($entityTypeName);
          $entityKeys = $entityStorage->getEntityType()->getKeys();
          $query[$entityTypeName] = $this->database->select($entityTypeName . '_field_data', 'nfd');
          $query[$entityTypeName]->addField('nfd', $entityKeys['id']);
          $query[$entityTypeName]->addField('nfd', $entityKeys['label']);
          $query[$entityTypeName]->addField('nfd', 'created');
          $query[$entityTypeName]->addField('nfd', 'status');
          $query[$entityTypeName]->addField('nfd', 'type');

          if ($filters['content_type']) {
            $query[$entityTypeName]->condition('nfd.type', $filters['content_type']);
          }

          // Join with users table to get the username who added the entity.
          $query[$entityTypeName]->addField('ufd', 'name', 'username');
          $query[$entityTypeName]->addField('nfd', $entityKeys['uid']);
          $query[$entityTypeName]->condition('nfd.' . $entityKeys['id'], $entityIds[$entityTypeName], 'in');
          $query[$entityTypeName]->innerJoin('users_field_data', 'ufd', 'nfd.' . $entityKeys['uid'] . ' = ufd.uid');

          // Filter by Starting Date.
          if (KanbanFilterForm::getDateRangeFilter()) {

            $searchFromTime = time() - (86400 * KanbanFilterForm::getDateRangeFilter());

            //@todo how about non mysql systems?
            $query[$entityTypeName]->condition('nfd.created',$searchFromTime,'>=');
          }


          // Sort.
          if ($this->database->schema()->fieldExists($entityTypeName . '_field_data', 'publish_on') && $this->contentCalendarModuleIsEnabled()) {
            $query[$entityTypeName]->orderBy('nfd.publish_on', 'ASC');
          }
          else {
            $query[$entityTypeName]->orderBy('nfd.created', 'ASC');
          }

          $result[$entityTypeName] = $query[$entityTypeName]->execute()->fetchAll();
        }
        catch (InvalidPluginDefinitionException $e) {
          watchdog_exception('content_kanban', $e);
        }
        catch (PluginNotFoundException $e) {
          watchdog_exception('content_kanban', $e);
        }
      }
    }
    if ($result) {
      return $result;
    }
    return [];
  }

}
