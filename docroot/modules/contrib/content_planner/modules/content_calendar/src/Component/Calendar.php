<?php

namespace Drupal\content_calendar\Component;

use Drupal\content_calendar\ContentTypeConfigService;
use Drupal\content_calendar\ContentCalendarService;
use Drupal\content_calendar\DateTimeHelper;
use Drupal\Core\Session\AccountProxyInterface;

/**
 *
 */
class Calendar {

  /**
   * Drupal\content_calendar\ContentTypeConfigService definition.
   *
   * @var \Drupal\content_calendar\ContentTypeConfigService
   */
  protected $contentTypeConfigService;

  /**
   * @var \Drupal\content_calendar\ContentCalendarService
   */
  protected $contentCalendarService;

  /**
   * @var \Drupal\content_calendar\Entity\ContentTypeConfig[]
   */
  protected $contentTypeConfigEntities;

  /**
   * Desired months to be rendered.
   *
   * @var int
   */
  protected $month;

  /**
   * Desired year to be rendered.
   *
   * @var intinYYYYFormat
   */
  protected $year;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $user;

  /**
   * Calendar constructor.
   *
   * @param \Drupal\content_calendar\ContentTypeConfigService $content_type_config_service
   *   The content type config service.
   * @param \Drupal\content_calendar\ContentCalendarService $content_calendar_service
   *   The content calendar service.
   * @param int $month
   *   The month to display in the calendar.
   * @param int $year
   *   The year to display in the calendar.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The current user.
   */
  public function __construct(
    ContentTypeConfigService $content_type_config_service,
    ContentCalendarService $content_calendar_service,
    $month,
    $year,
    AccountProxyInterface $user
  ) {
    $this->contentTypeConfigService = $content_type_config_service;
    $this->contentCalendarService = $content_calendar_service;
    $this->contentTypeConfigEntities = $this->contentTypeConfigService->loadAllEntities();
    $this->month = $month;
    $this->year = $year;
    $this->user = $user;
  }

  /**
   * Generates a calendar id.
   *
   * @return string
   *   The calendar id.
   */
  protected function generateCalendarId() {
    return $this->year . '-' . $this->month;
  }

  /**
   * Creates the render array for the calendar.
   *
   * @return array
   *   The render array of the calendar.
   */
  public function build() {

    // Build data structure first.
    $calendar = $this->buildCalendarDataStructure();

    // Get nodes per node type.
    $node_basic_data = [];

    foreach ($this->contentTypeConfigEntities as $node_type => $config_entity) {
      $node_basic_data[$node_type] = $this->contentCalendarService->getNodesByType(
        $node_type,
        [
          'month' => $this->month,
          'year' => $this->year,
        ]
      );
    }

    // Place nodes in Calendars.
    $this->placeNodesInCalendars($calendar, $node_basic_data);
    // Get the weekdays based on the Drupal first day of week setting.

    $build = [
      '#theme' => 'content_calendar',
      '#calendar' => $calendar,
      '#node_type_creation_permissions' => $this->getPermittedNodeTypeCreationActions(),
      '#attached' => [
        'library' => ['content_calendar/calendar'],
      ],
    ];

    return $build;
  }

  /**
   * Get all permitted Node Type Creation actions.
   *
   * @return array
   */
  protected function getPermittedNodeTypeCreationActions() {

    $permitted_node_types = [];

    foreach ($this->contentTypeConfigEntities as $node_type => $config_entity) {

      if ($this->user->hasPermission("create $node_type content")) {
        $permitted_node_types[$node_type] = $config_entity;
      }

    }

    return $permitted_node_types;
  }

  /**
   * Build data structure for Calendar.
   *
   * @return array
   *   The data for the calendar.
   *
   * @throws \Exception
   */
  protected function buildCalendarDataStructure() {

    $today_datetime = new \DateTime();
    $today_datetime->setTime(0, 0, 0);

    $one_day_interval = new \DateInterval('P1D');

    // Get the first date of a given month.
    $datetime = DateTimeHelper::getFirstDayOfMonth($this->month, $this->year);

    $scaffold_data = [
      'calendar_id' => $this->generateCalendarId(),
      'month' => $this->month,
      'year' => $this->year,
      'label' => DateTimeHelper::getMonthLabelByNumber($this->month) . ' ' . $this->year,
      'first_date_weekday' => $datetime->format('N'),
      'days' => [],
    ];

    // Calculate the days in a month.
    $days_in_month = DateTimeHelper::getDayCountInMonth($this->month, $this->year);

    // Build all dates in a month.
    $i = 1;
    while ($i <= $days_in_month) {

      $scaffold_data['days'][] = [
        'date' => $datetime->format('Y-m-d'),
        'day' => $datetime->format('j'),
        'weekday' => $datetime->format('N'),
        'nodes' => [],
        'is_today' => ($today_datetime == $datetime) ? TRUE : FALSE,
      ];

      $i++;
      $datetime->add($one_day_interval);
    }

    return $scaffold_data;

  }

  /**
   * Place Nodes in Calendar.
   *
   * @param array $calendar
   *
   * @param array $node_basic_data
   */
  protected function placeNodesInCalendars(array &$calendar, array $node_basic_data) {

    foreach ($node_basic_data as $node_type => $node_rows) {

      foreach ($node_rows as $node_row) {

        $calendar_entry = new CalendarEntry(
          $this->month,
          $this->year,
          $this->getNodeTypeConfig($node_type),
          $node_row
        );

        foreach ($calendar['days'] as &$day) {

          // If date of entry is the current date of the calendar day.
          if ($day['date'] == $calendar_entry->formatSchedulingDateAsMySQLDateOnly()) {

            // Generate a unique key within the day for the entry.
            $key = $calendar_entry->getRelevantDate() . '_' . $calendar_entry->getNodeID();

            $day['nodes'][$key] = $calendar_entry->build();

            // Sort by keys.
            ksort($day['nodes']);
          }

        }
      }

    }

  }

  /**
   * Get Content Type config entity by Node Type.
   *
   * @param string $node_type
   *   The node type id to get the config.
   *
   * @return bool|\Drupal\content_calendar\Entity\ContentTypeConfig
   *   The content type config.
   */
  protected function getNodeTypeConfig($node_type) {

    if (array_key_exists($node_type, $this->contentTypeConfigEntities)) {
      return $this->contentTypeConfigEntities[$node_type];
    }

    return FALSE;
  }

}
