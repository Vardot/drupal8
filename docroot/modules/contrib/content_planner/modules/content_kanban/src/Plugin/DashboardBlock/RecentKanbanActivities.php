<?php

namespace Drupal\content_kanban\Plugin\DashboardBlock;

use Drupal\content_kanban\Entity\KanbanLog;
use Drupal\content_planner\DashboardBlockBase;
use Drupal\content_planner\UserProfileImage;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\content_kanban\KanbanWorkflowService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a user block for Content Planner Dashboard.
 *
 * @DashboardBlock(
 *   id = "recent_kanban_activities",
 *   name = @Translation("Recent Kanban Activities")
 * )
 */
class RecentKanbanActivities extends DashboardBlockBase {

  /**
   * An integer representing the default query limit.
   *
   * @var int
   */
  protected $defaultLimit = 10;

  /**
   * The date formatter object.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);

    $this->dateFormatter = $date_formatter;
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
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigSpecificFormFields(FormStateInterface &$form_state,
                                              Request &$request,
                                              array $block_configuration) {

    $form = [];

    $limit_default_value = $this->getCustomConfigByKey($block_configuration, 'limit', $this->defaultLimit);

    // Limit.
    $form['limit'] = [
      '#type' => 'number',
      '#title' => t('Quantity'),
      '#required' => TRUE,
      '#default_value' => $limit_default_value,
    ];

    $user_picture_field_exists = !\Drupal::config('field.field.user.user.user_picture')->isNew();

    $show_user_thumb_default_value = $limit_default_value = $this->getCustomConfigByKey($block_configuration, 'show_user_thumb', 0);

    $form['show_user_thumb'] = [
      '#type' => 'checkbox',
      '#title' => t('Show thumbnail image of User image'),
      '#description' => t('This option is only available, if the User account has the "user_picture" field. See Account configuration.'),
      '#disabled' => !$user_picture_field_exists,
      '#default_value' => $show_user_thumb_default_value,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [];

    // Get config.
    $config = $this->getConfiguration();

    // Get limit.
    $limit = $this->getCustomConfigByKey($config, 'limit', $this->defaultLimit);

    /* @var $kanban_log_service \Drupal\content_kanban\KanbanLogService */
    $kanban_log_service = \Drupal::service('content_kanban.kanban_log_service');

    // Get Logs.
    if ($logs = $kanban_log_service->getRecentLogs($limit, ['exclude_anonymous_users' => TRUE])) {
      $entries = $this->buildKanbanLogActivities($logs);
      $build = [
        '#theme' => 'content_kanban_log_recent_activity',
        '#entries' => $entries,
        '#show_user_thumb' => $this->getCustomConfigByKey($config, 'show_user_thumb', 0),
      ];

    }

    return $build;
  }

  /**
   * Builds the log entries.
   *
   * @param array $logs
   *   An array with the logs.
   *
   * @return array
   *   Returns an array with the logs.
   */
  protected function buildKanbanLogActivities(array $logs) {

    $entries = [];

    foreach ($logs as $log) {

      // Get User object.
      $user = $log->getOwner();
      // Get Entity object.
      $entity = $log->getEntityObject();
      // If the Entity or user cannot be found, then continue with the next log.
      if (!$entity || !$user) {
        continue;
      }

      if ($message = $this->composeMessage($log, $user, $entity)) {

        $entry = [
          'user_profile_image' => UserProfileImage::generateProfileImageUrl($user, 'content_kanban_user_thumb'),
          'username' => $user->getAccountName(),
          'message' => $message,
        ];

        $entries[] = $entry;

      }

    }

    return $entries;
  }

  /**
   * Composes the message.
   *
   * @param \Drupal\content_kanban\Entity\KanbanLog $log
   *   The Kanban log object.
   * @param \Drupal\user\Entity\User $user
   *   The User object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return string
   *   Returns a string containing the composed message.
   */
  protected function composeMessage(KanbanLog $log, User $user, EntityInterface $entity) {

    $state_from = $log->getStateFrom();
    $state_to = $log->getStateTo();
    $workflow_states = KanbanWorkflowService::getWorkflowStates($log->getWorkflow());

    if ($state_from == $state_to) {

      $message = t(
        '@username has updated @entity_type "@entity" @time ago',
        [
          '@username' => $user->getAccountName(),
          '@entity' => $entity->label(),
          '@entity_type' => ucfirst($entity->getEntityTypeId()),
          '@time' => $this->calculateTimeAgo($log),
        ]
      );

    }
    else {

      $message = t(
        '@username has changed the state of @entity_type "@entity" from "@state_from" to "@state_to" @time ago',
        [
          '@username' => $user->getAccountName(),
          '@entity' => $entity->label(),
          '@entity_type' => ucfirst($entity->getEntityTypeId()),
          '@time' => $this->calculateTimeAgo($log),
          '@state_from' => $workflow_states[$state_from],
          '@state_to' => $workflow_states[$state_to],
        ]
          );

    }

    return $message;
  }

  /**
   * Gets the time difference for the given log since the created time.
   *
   * @param \Drupal\content_kanban\Entity\KanbanLog $log
   *   The Kanban log object.
   *
   * @return mixed
   *   Returns the calculated time ago for the given Kanban log.
   */
  protected function calculateTimeAgo(KanbanLog $log) {
    return $this->dateFormatter->formatTimeDiffSince($log->getCreatedTime());
  }

}
