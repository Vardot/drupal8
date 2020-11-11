<?php

namespace Drupal\content_planner;

/**
 * Class DashboardService.
 */
class DashboardService {

  /**
   * The dashboard settings service.
   *
   * @var \Drupal\content_planner\DashboardSettingsService
   */
  protected $dashboardSettingsService;

  /**
   * Constructs a new DashboardService object.
   */
  public function __construct(DashboardSettingsService $dashboard_settings_service) {
    $this->dashboardSettingsService = $dashboard_settings_service;
  }

  /**
   * Gets the dashboard settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The current dashboard config.
   */
  public function getDashboardSettings() {
    return $this->dashboardSettingsService->getSettings();
  }

  /**
   * Check if the Content Calendar is enabled.
   *
   * @return bool
   *   TRUE if the content calendar is enabled.
   */
  public function isContentCalendarEnabled() {
    return \Drupal::moduleHandler()->moduleExists('content_calendar');
  }

  /**
   * Check if the Content Kanban is enabled.
   *
   * @return bool
   *   TRUE if the kanban calendar is enabled.
   */
  public function isContentKanbanEnabled() {
    return \Drupal::moduleHandler()->moduleExists('content_kanban');
  }

}
