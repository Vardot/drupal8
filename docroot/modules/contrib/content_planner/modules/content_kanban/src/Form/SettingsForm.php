<?php

namespace Drupal\content_kanban\Form;

use Drupal\content_kanban\KanbanService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures forms module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config name.
   *
   * @var string
   */
  static public $configName = 'content_kanban.settings';

  /**
   *  Default Date Range value.
   */
  Const DEFAULT_DATE_RANGE_VALUE = 30;

  /**
   * The stored config for the form.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The Kanban service.
   *
   * @var \Drupal\content_kanban\KanbanService
   */
  protected $kanbanService;

  /**
   * Constructor for the Settings Form.
   */
  public function __construct(KanbanService $kanbanService, ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

    $this->kanbanService = $kanbanService;

    // Get config.
    $this->config = $this->config(self::$configName);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_kanban.kanban_service'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_kanban_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'content_kanban.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    // If the Content Calendar module is not enabled, set the use color setting
    // to inactive.
    if (!$this->kanbanService->contentCalendarModuleIsEnabled()) {
      $this->saveColorSetting(0);
    }

    $config = $this->config(self::$configName);

    $form['advice'] = [
      '#title' => 'Disclaimer',
      '#type' => 'fieldset',
      '#weight' => 78,
      '#description' => $this->t('Ensure all workflow state are set as a Default Revision in order to allow kanban table to work properly'),
    ];

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    // Content Calendar colors.
    $form['options']['use_content_calendar_colors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use the defined colors from Content Calendar'),
      '#description' => $this->t('This setting is only available if the Content Calendar is enabled and configured properly.'),
      '#default_value' => $config->get('use_content_calendar_colors'),
      '#disabled' => !$this->kanbanService->contentCalendarModuleIsEnabled(),
    ];

    // Show user thumb checkbox.
    $user_picture_field_exists = !$this->config('field.field.user.user.user_picture')->isNew();

    $form['options']['show_user_thumb'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show thumbnail image of User image'),
      '#description' => $this->t('This option is only available, if the User account has the "user_picture" field. See Account configuration.'),
      '#disabled' => !$user_picture_field_exists,
      '#default_value' => $this->config->get('show_user_thumb'),
    ];

    $default_date_range_value = self::DEFAULT_DATE_RANGE_VALUE;

    if ($this->config->get('default_filter_date_range')) {
      $default_date_range_value = $this->config->get('default_filter_date_range');
    }

    $form['options']['default_filter_date_range'] = [
       '#type' => 'select',
       '#title' => $this->t('Date range'),
       '#options' => \Drupal\content_kanban\Form\KanbanFilterForm::getDateRangeOptions(),
       '#required' => FALSE,
       '#empty_value' => '',
       '#empty_option' => $this->t('All'),
       '#default_value' => $default_date_range_value,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get values.
    $values = $form_state->getValues();

    // Get value to use Content Calendar colors.
    $use_content_calendar_colors = $values['use_content_calendar_colors'];

    // If Content Calendar module is disabled, then disable usage of colors.
    if (!$this->kanbanService->contentCalendarModuleIsEnabled()) {
      $use_content_calendar_colors = 0;
    }

    // Save settings into configuration.
    $this->saveColorSetting($use_content_calendar_colors);

    // Save show user image thumbnail option.
    $this->config(self::$configName)
      ->set('show_user_thumb', $values['show_user_thumb'])
      ->set('default_filter_date_range', $values['default_filter_date_range'])
      ->save();
  }

  /**
   * Saves the color setting.
   *
   * @param int $value
   *   The "use content calendar colors" setting value.
   */
  protected function saveColorSetting($value) {
    $this->config(self::$configName)
      ->set('use_content_calendar_colors', $value)
      ->save();
  }

}
