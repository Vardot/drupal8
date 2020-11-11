<?php

namespace Drupal\content_kanban\Plugin\DashboardBlock;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\content_planner\DashboardBlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\workflows\Entity\Workflow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a Dashboard block for Content Planner Dashboard.
 *
 * @DashboardBlock(
 *   id = "content_state_statistic",
 *   name = @Translation("Content Status Widget")
 * )
 */
class ContentStateStatistic extends DashboardBlockBase implements ContainerFactoryPluginInterface {

  use MessengerTrait;

  /**
   * The Kanban Statistic service.
   *
   * @var \Drupal\content_kanban\KanbanStatisticService
   */
  protected $kanbanStatisticService;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, 
    $plugin_id, 
    $plugin_definition, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->setMessenger($messenger);
    $this->kanbanStatisticService = \Drupal::service('content_kanban.kanban_statistic_service');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getConfigSpecificFormFields(FormStateInterface &$form_state,
                                              Request &$request,
                                              array $block_configuration) {

    $form = [];

    $workflow_options = [];

    // Get all workflows.
    $workflows = Workflow::loadMultiple();

    /* @var $workflow \Drupal\workflows\Entity\Workflow */
    foreach ($workflows as $workflow) {

      if ($workflow->status()) {
        $workflow_options[$workflow->id()] = $workflow->label();
      }
    }

    $form['workflow_id'] = [
      '#type' => 'select',
      '#title' => t('Select workflow'),
      '#required' => TRUE,
      '#options' => $workflow_options,
      '#default_value' => $this->getCustomConfigByKey($block_configuration, 'workflow_id', ''),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get config.
    $config = $this->getConfiguration();

    // Get Workflow ID from config.
    $workflow_id = $this->getCustomConfigByKey($config, 'workflow_id', '');

    // Load workflow.
    $workflow = Workflow::load($workflow_id);

    // If workflow does not exist.
    if (!$workflow) {
      $message = t('Content Status Statistic: Workflow with ID @id does not exist. Block will not be shown.', ['@id' => $workflow_id]);
      $this->messenger()->addError($message);
      return [];
    }

    // Get data.
    $data = $this->kanbanStatisticService->getWorkflowStateContentCounts($workflow);

    $build = [
      '#theme' => 'content_state_statistic',
      '#data' => $data,
      '#attached' => [
        'library' => ['content_kanban/content_state_statistic'],
      ],
    ];

    return $build;
  }

}
