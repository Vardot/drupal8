<?php

namespace Drupal\content_kanban\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_kanban\KanbanService;
use Drupal\content_kanban\KanbanWorkflowService;
use Drupal\content_kanban\Component\Kanban;
use Drupal\content_moderation\ModerationInformation;
use Drupal\content_moderation\StateTransitionValidation;
use Drupal\workflows\Entity\Workflow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class KanbanController.
 */
class KanbanController extends ControllerBase {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Kanban Service.
   *
   * @var \Drupal\content_kanban\KanbanService
   */
  protected $kanbanService;

  /**
   * The Moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $moderationInformation;

  /**
   * The State Transition Validation.
   *
   * @var \Drupal\content_moderation\StateTransitionValidation
   */
  protected $stateTransitionValidation;

  /**
   * Constructs a new KanbanController object.
   */
  public function __construct(
    AccountInterface $current_user,
    KanbanService $kanban_service,
    ModerationInformation $moderation_information,
    StateTransitionValidation $state_transition_validation
  ) {
    $this->currentUser = $current_user;
    $this->kanbanService = $kanban_service;
    $this->moderationInformation = $moderation_information;
    $this->stateTransitionValidation = $state_transition_validation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('content_kanban.kanban_service'),
      $container->get('content_moderation.moderation_information'),
      $container->get('content_moderation.state_transition_validation')
    );
  }

  /**
   * Show Kanbans.
   *
   * @return array
   *   A renderable array with the Kanbans.
   *
   * @throws \Exception
   */
  public function showKanbans() {
    $build = [];

    $workflows = Workflow::loadMultiple();

    if (!$workflows) {
      $this->messenger()->addMessage($this->t('There are no Workflows configured yet.'), 'error');
      return [];
    }

    foreach ($workflows as $workflow) {

      if (Kanban::isValidContentModerationWorkflow($workflow)) {

        $kanban = new Kanban(
          $this->currentUser,
          $this->kanbanService,
          $workflow
        );

        $build[] = $kanban->build();
      }

    }

    // If there are no Kanbans, display a message.
    if (!$build) {

      $link = Url::fromRoute('entity.workflow.collection')->toString();

      $message = $this->t('To use Content Kanban, you need to have a valid Content Moderation workflow with at least one Entity Type configured. Please go to the <a href="@link">Workflow</a> configuration.', ['@link' => $link]);
      $this->messenger()->addMessage($message, 'error');
    }

    return $build;
  }

  /**
   * Updates the Workflow state of a given Entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The current entity.
   * @param string $state_id
   *   The target state id for the current entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns a JSON response with the result of the update process.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateEntityWorkflowState(ContentEntityInterface $entity, $state_id) {
    $data = [
      'success' => FALSE,
      'message' => NULL,
    ];

    // Check if entity is moderated.
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      $data['message'] = $this->t('Entity @type with ID @id is not a moderated entity.', ['@id' => $entity->id(), '@type' => $entity->getEntityTypeId()]);
      return new JsonResponse($data);
    }

    // Get Workflow from entity.
    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    // If Workflow does not exist.
    if (!$workflow) {
      $data['message'] = $this->t('Workflow not found for Entity @type with ID @id.', ['@id' => $entity->id(), '@type' => $entity->getEntityTypeId()]);
      return new JsonResponse($data);
    }

    // Get Workflow States.
    $workflow_states = KanbanWorkflowService::getWorkflowStates($workflow);
    // Check if state given by request matches any of the Workflow's states.
    if (!array_key_exists($state_id, $workflow_states)) {

      $data['message'] = $this->t(
        'Workflow State @state_id is not a valid state of Workflow @workflow_id.',
        [
          '@state_id' => $state_id,
          '@workflow_id' => $workflow->id(),
        ]
      );
      return new JsonResponse($data);
    }

    // Load current workflow state of entity.
    $current_state = $entity->get('moderation_state')->getValue()[0]['value'];

    // Load all valid transitions.a1
    $allowed_transitions = $this->stateTransitionValidation->getValidTransitions($entity, $this->currentUser);

    // Load all available transitions.
    $transitions = $workflow->get('type_settings')['transitions'];

    foreach ($transitions as $key => $transition) {
      if (in_array($current_state, $transition['from']) && $transition['to'] == $state_id) {
        $transition_id = $key;
        continue;
      }
    }

    if (empty($transition_id)) {
      $data['message'] = $this->t('Invalid transition');
      return new JsonResponse($data);
    }

    if(!array_key_exists($transition_id, $allowed_transitions)) {
      $data['message'] = $this->t(
        'You do not have permissions to perform the action @transition_id for this content. Please contact the site administrator.',
        [
          '@transition_id' => $transition_id,
        ]
      );
      return new JsonResponse($data);
    }

    // Set new state.
    $entity->moderation_state->value = $state_id;

    // Save.
    if ($entity->save() == SAVED_UPDATED) {
      $data['success'] = TRUE;
      $data['message'] = $this->t(
        'Workflow state of Entity @type with @id has been updated to @state_id',
        [
          '@type' => $entity->getEntityTypeId(),
          '@id' => $entity->id(),
          '@state_id' => $state_id,
        ]
      );
    }

    return new JsonResponse($data);
  }

}
