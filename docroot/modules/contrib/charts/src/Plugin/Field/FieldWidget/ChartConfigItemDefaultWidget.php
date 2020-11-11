<?php

namespace Drupal\charts\Plugin\Field\FieldWidget;

use Drupal\charts\Services\ChartsSettingsService;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'chart_config_default' widget.
 *
 * @FieldWidget(
 *   id = "chart_config_default",
 *   label = @Translation("Chart"),
 *   field_types = {
 *     "chart_config",
 *   },
 * )
 */
class ChartConfigItemDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The default chart settings.
   *
   * @var \Drupal\charts\Services\ChartsSettingsService
   */
  protected $chartsDefaultSettings;

  /**
   * Constructs a ChartItemDefaultWidget instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\charts\Services\ChartsSettingsService $charts_settings
   *   Default chart settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ChartsSettingsService $charts_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->chartsDefaultSettings = $charts_settings->getChartsSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('charts.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'change_default_library' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['change_default_library'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to change the default charting library'),
      '#description' => $this->t('The default charting library can be updated at <a href="/admin/config/content/charts">the chart settings</a> page.'),
      '#default_value' => $this->getSetting('change_default_library'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $change_default_library = $this->getSetting('change_default_library');
    if (empty($change_default_library)) {
      $summary[] = $this->t('User is not allowed to change/set the default charting library');
    }
    else {
      $summary[] = $this->t('User is allowed to change/set the charting library');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $value = !is_null($item->toArray()['config']) ? $item->toArray()['config'] : [];
    $change_default_library = $this->getSetting('change_default_library');
    $library = '';
    if (!empty($this->chartsDefaultSettings)) {
      $value = NestedArray::mergeDeep($this->chartsDefaultSettings, $value);
      if (empty($change_default_library) && !empty($this->chartsDefaultSettings['library'])) {
        $library = $this->chartsDefaultSettings['library'];
      }
    }

    $element += [
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $element['config'] = [
      '#type' => 'charts_settings',
      '#used_in' => 'basic_form',
      '#required' => $element['#required'],
      '#series' => TRUE,
      '#default_value' => $value ?? [],
      '#library' => $library,
    ];

    // Make the element none required for all at the default value widget.
    if ($this->isDefaultValueWidget($form_state)) {
      $element['config']['#required'] = FALSE;
    }

    return $element;
  }

}
