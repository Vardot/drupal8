<?php

namespace Drupal\content_kanban\Component;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\content_kanban\Form\KanbanFilterForm;
use Drupal\content_kanban\KanbanService;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\workflows\Entity\Workflow;

/**
 * The main Kanban class.
 */
class Kanban {

  /**
   * The request service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Kanban service.
   *
   * @var \Drupal\content_kanban\KanbanService
   */
  protected $kanbanService;

  /**
   * The workflow service.
   *
   * @var \Drupal\workflows\Entity\Workflow
   */
  protected $workflow;

  /**
   * The workflow ID.
   *
   * @var string
   */
  protected $workflowID;

  /**
   * The type settings.
   *
   * @var array
   */
  protected $typeSettings = [];

  /**
   * The entity types.
   *
   * @var array
   */
  protected $entityTypes = [];

  /**
   * The states.
   *
   * @var array
   */
  protected $states = [];

  /**
   * Constructor for the Kanban class.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user service.
   * @param \Drupal\content_kanban\KanbanService $kanban_service
   *   The Kanban service.
   * @param \Drupal\workflows\Entity\Workflow $workflow
   *   The workflow service.
   *
   * @throws \Exception
   */
  public function __construct(AccountInterface $current_user, KanbanService $kanban_service, Workflow $workflow) {

    if (!self::isValidContentModerationWorkflow($workflow)) {
      throw new \Exception('The given workflow is no valid Content Moderation Workflow');
    }

    // Store request object.
    $this->request = \Drupal::request();

    // Store current user.
    $this->currentUser = $current_user;

    // Store Kanban service.
    $this->kanbanService = $kanban_service;

    // Store Workflow.
    $this->workflow = $workflow;

    // Store Workflow ID.
    $this->workflowID = $workflow->get('id');

    // Store Type settings.
    $this->typeSettings = $workflow->get('type_settings');
    // Store Entity types this workflow applies to.
    $this->entityTypes = $this->typeSettings['entity_types'];
    // Store states.
    $this->states = $this->sortStates($this->typeSettings['states']);
  }

  /**
   * Sorts the given states.
   *
   * @param array $states
   *   An array with the states to sort.
   *
   * @return array
   *   Returns the sorted array of states.
   */
  protected function sortStates(array $states) {

    // Make a copy of the states.
    $sorted_states = $states;

    // Add the state id, so it does not get lost during the custom sort
    // function.
    foreach ($sorted_states as $state_id => &$state) {
      $state['state_id'] = $state_id;
    }

    // Sort for weight.
    usort($sorted_states, function ($a, $b) {
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

    // Build a new return array.
    $return = [];

    foreach ($sorted_states as $sorted_state) {
      $return[$sorted_state['state_id']] = $sorted_state;
    }

    return $return;
  }

  /**
   * Checks if a given workflow is a valid Content Moderation workflow.
   *
   * @param \Drupal\workflows\Entity\Workflow $workflow
   *   The workflow service.
   *
   * @return bool
   *   Returns TRUE if the workflow is valid, FALSE otherwise.
   */
  public static function isValidContentModerationWorkflow(Workflow $workflow) {

    if ($workflow->get('type') == 'content_moderation') {
      $type_settings = $workflow->get('type_settings');

      if (!empty($type_settings['entity_types'])) {
        if (array_key_exists('states', $type_settings)) {
          if (!empty($type_settings['states'])) {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Build.
   *
   * @return array
   *   Returns a renderable array with the build of the Kanban board.
   */
  public function build() {

    $columns = [];
    // Get all Entity Type configs.
    $entityTypeConfigs = $this->kanbanService->getEntityTypeConfigs($this->entityTypes);
    // Get User ID filter.
    $filter_uid = KanbanFilterForm::getUserIdFilter();

    // If the user cannot edit any content, hide the Filter form.
    if (!$this->currentUser->hasPermission('manage any content with content kanban')) {
      $filter_uid = $this->currentUser->id();
    }

    // Get content type filter.
    $filter_content_type = KanbanFilterForm::getContentTypeFilter();

    // Get State filter.
    $filter_state = KanbanFilterForm::getStateFilter();

    foreach ($this->states as $state_id => $state) {

      // If the State filter has been set, only get data which set by the filter.
      if ($filter_state && $filter_state != $state_id) {
        // Add empty Kanban column when the column is filtered.
        $emptyColumn = new KanbanColumn(
          $this->workflowID,
          $state_id,
          $state,
          [],
          $entityTypeConfigs
        );
        $columns[] = $emptyColumn->build();

        continue;
      }

      // Prepare filter for the Kanban service.
      $filters = [
        'moderation_state' => $state_id,
      ];

      // Add User filter, if given.
      if ($filter_uid) {
        $filters['uid'] = $filter_uid;
      }

      if ($filter_content_type) {
        $filters['content_type'] = $filter_content_type;
      }
      else {
        $filters['content_type'] = FALSE;
      }

      // Get Entity IDs.
      $multipleEntities = [];
      if ($entityIds = $this->kanbanService->getEntityIdsFromContentModerationEntities($this->workflowID, $filters, $this->entityTypes)) {
        $multipleEntities = $this->kanbanService->getEntitiesByEntityIds($entityIds, $filters);
      }
      $columnEntities = [];
      foreach ($multipleEntities as $entities) {
        $columnEntities = array_merge($columnEntities, $entities);
      }
      // Create Kanban object.
      $kanban_column = new KanbanColumn(
        $this->workflowID,
        $state_id,
        $state,
        $columnEntities,
        $entityTypeConfigs
      );

      // Build render array for Kanban.
      $columns[] = $kanban_column->build();
    }

    // Permissions.
    $permissions = [
      'create_entity' => $this->getCreateEntityPermissions($entityTypeConfigs),
    ];
    // Build render array for Kanban.
    $build = [
      '#theme' => 'content_kanban',
      '#kanban_id' => $this->workflowID,
      '#kanban_label' => $this->workflow->label(),
      '#filter_form' => $this->buildFilterForm(),
      '#permissions' => $permissions,
      '#headers' => $this->buildHeaders(),
      '#columns' => $columns,
      '#attached' => [
        'library' => ['content_kanban/kanban'],
      ],
    ];

    return $build;

  }

  /**
   * Builds headers for table.
   *
   * @return array
   *   Returns an array with the table headers.
   */
  protected function buildHeaders() {

    $headers = [];

    foreach ($this->states as $state) {
      $headers[] = $state['label'];
    }

    return $headers;
  }

  /**
   * Gets the list of permissions the current user may create a Entity type.
   *
   * @param \Drupal\content_kanban\EntityTypeConfig[] $entity_type_configs
   *   An array with the entity type configs.
   *
   * @return array
   *   Returns an array with the permissions.
   */
  protected function getCreateEntityPermissions(array $entity_type_configs) {

    $permissions = [];
    foreach ($entity_type_configs as $entity_type_id => $entity_type_config) {
      // Check if the current user has the permisson to create a certain Entity
      // type.
      if ($this->currentUser->hasPermission("create $entity_type_id content")) {
        $permissions[$entity_type_id] = t("Add @type", ['@type' => $entity_type_config->getLabel()]);
      }

    }

    return $permissions;
  }

  /**
   * Builds the Filter form.
   *
   * @return array
   *   Returns an array with the filter form.
   */
  protected function buildFilterForm() {

    // If the user cannot edit any content, hide the Filter form.
    if (!$this->currentUser->hasPermission('manage any content with content kanban')) {
      return [];
    }

    // Get Filter form.
    $form_params = [
      'workflow_id' => $this->workflowID,
      'states' => $this->states,
    ];
    $filter_form = \Drupal::formBuilder()->getForm('Drupal\content_kanban\Form\KanbanFilterForm', $form_params);

    // Remove certain needed form properties.
    unset($filter_form['form_build_id']);
    unset($filter_form['form_id']);

    return $filter_form;
  }

}
