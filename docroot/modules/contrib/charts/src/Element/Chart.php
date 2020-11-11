<?php

namespace Drupal\charts\Element;

use Drupal\charts\Plugin\chart\Library\ChartBase;
use Drupal\charts\Plugin\chart\Library\ChartInterface;
use Drupal\charts\ChartManager;
use Drupal\charts\Services\ChartsSettingsServiceInterface;
use Drupal\charts\TypeManager;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a chart render element.
 *
 * @RenderElement("chart")
 */
class Chart extends RenderElement implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * The chart plugin manager.
   *
   * @var \Drupal\charts\ChartManager
   */
  protected $chartsManager;

  /**
   * The chart type info service.
   *
   * @var \Drupal\charts\TypeInterface
   */
  protected $chartsTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The charts settings.
   *
   * @var array
   */
  protected $chartSettings;

  /**
   * Constructs Chart object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\charts\ChartManager $chart_manager
   *   The chart plugin manager.
   * @param \Drupal\charts\TypeManager $type_manager
   *   The chart type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\charts\Services\ChartsSettingsServiceInterface $chart_settings
   *   The chart settings.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ChartManager $chart_manager, TypeManager $type_manager, ModuleHandlerInterface $module_handler, ChartsSettingsServiceInterface $chart_settings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->chartsManager = $chart_manager;
    $this->chartsTypeManager = $type_manager;
    $this->moduleHandler = $module_handler;
    $this->chartSettings = $chart_settings->getChartsSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.charts'),
      $container->get('plugin.manager.charts_type'),
      $container->get('module_handler'),
      $container->get('charts.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#chart_type' => NULL,
      '#chart_library' => NULL,
      '#chart_id' => NULL,
      '#title' => NULL,
      '#title_color' => '#000',
      '#title_font_weight' => 'normal',
      '#title_font_style' => 'normal',
      '#title_font_size' => 14,
      '#title_position' => 'out',
      '#colors' => ChartBase::getDefaultColors(),
      '#font' => 'Arial',
      '#font_size' => 12,
      '#background' => 'transparent',
      '#stacking' => NULL,
      '#pre_render' => [
        [$this, 'preRender'],
      ],
      '#tooltips' => TRUE,
      '#tooltips_use_html' => FALSE,
      '#data_labels' => FALSE,
      '#legend' => TRUE,
      '#legend_title' => '',
      '#legend_title_font_weight' => 'bold',
      '#legend_title_font_style' => 'normal',
      '#legend_title_font_size' => '',
      '#legend_position' => 'right',
      '#legend_font_weight' => 'normal',
      '#legend_font_style' => 'normal',
      '#legend_font_size' => NULL,
      '#width' => NULL,
      '#height' => NULL,
      '#attributes' => [],
      '#chart_definition' => [],
      '#raw_options' => [],
    ];
  }

  /**
   * Main #pre_render callback to expand a chart element.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   The chart element.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function preRender(array $element) {
    /** @var \Drupal\charts\Plugin\chart\Library\ChartInterface[] $definitions */
    $definitions = $this->chartsManager->getDefinitions();

    if (!$definitions) {
      $element['#type'] = 'markup';
      $element['#markup'] = $this->t('No charting library found. Enable a charting module such as Google Charts or Highcharts.');
      return $element;
    }

    // Ensure there's an x and y axis to provide defaults.
    $type_name = $element['#chart_type'];
    /** @var \Drupal\charts\Plugin\chart\Type\TypeInterface $type */
    $type = $this->chartsTypeManager->getDefinition($type_name);
    if ($type && $type['axis'] === ChartInterface::DUAL_AXIS) {
      foreach (Element::children($element) as $key) {
        $children_types[] = $element[$key]['#type'];
      }

      if (!in_array('chart_xaxis', $children_types)) {
        $element['xaxis'] = ['#type' => 'chart_xaxis'];
      }
      if (!in_array('chart_yaxis', $children_types)) {
        $element['yaxis'] = ['#type' => 'chart_yaxis'];
      }
    }

    self::castElementIntergerValues($element);

    // Generic theme function assuming it will be suitable for most chart types.
    $element['#theme'] = 'charts_chart';
    // Allow the chart to be altered - @TODO use event dispatching if needed.
    $alter_hooks = ['chart'];
    $chart_id = $element['#chart_id'];
    if ($chart_id) {
      $alter_hooks[] = 'chart_' . $element['#chart_id'];
    }
    $this->moduleHandler->alter($alter_hooks, $element, $chart_id);

    // Include the library specific render callback via their plugin manager.
    // Use the first charting library if the requested library is not available.
    $library = isset($element['#chart_library']) ? $element['#chart_library'] : '';
    $library = $this->getLibrary($library);

    $element['#chart_library'] = $library;

    $library_form = $library . '_settings';
    $plugin_configuration = $this->chartSettings[$library_form] ?? [];
    $plugin = $this->chartsManager->createInstance($library, $plugin_configuration);
    $element = $plugin->preRender($element);

    if (!empty($element['#chart_definition'])) {
      $chart_definition = $element['#chart_definition'];
      unset($element['#chart_definition']);

      // Allow the chart definition to be altered - @TODO use event dispatching
      // if needed.
      $alter_hooks = ['chart_definition'];
      if ($element['#chart_id']) {
        $alter_hooks[] = 'chart_definition_' . $chart_id;
      }
      $this->moduleHandler->alter($alter_hooks, $chart_definition, $element, $chart_id);
      // Set the element #chart_json property as a data-attribute.
      $element['#attributes']['data-chart'] = Json::encode($chart_definition);
    }

    $element['#cache']['tags'][] = 'config:charts.settings';

    return $element;
  }

  /**
   * Casts recursively integer values.
   *
   * @param array $element
   *   The element.
   */
  public static function castElementIntergerValues(array &$element) {
    // Cast options to integers to avoid redundant library fixing problems.
    $integer_options = [
      // Chart options.
      '#title_font_size',
      '#font_size',
      '#legend_title_font_size',
      '#legend_font_size',
      '#width',
      '#height',
      // Axis options.
      '#title_font_size',
      '#labels_font_size',
      '#labels_rotation',
      '#max',
      '#min',
      // Data options.
      '#decimal_count',
    ];

    foreach ($element as $property_name => $value) {
      if (is_array($element[$property_name])) {
        self::castElementIntergerValues($element[$property_name]);
      }
      elseif ($property_name && in_array($property_name, $integer_options)) {
        $element[$property_name] = (is_null($element[$property_name]) || strlen($element[$property_name]) === 0) ? NULL : (int) $element[$property_name];
      }
    }
  }

  /**
   * Trims out, recursively, empty options that aren't used.
   *
   * @param array $array
   *   The array to trim.
   */
  public static function trimArray(array &$array) {
    foreach ($array as $key => &$value) {
      if (is_array($value)) {
        self::trimArray($value);
      }
      elseif (is_null($value) || (is_array($value) && count($value) === 0)) {
        unset($array[$key]);
      }
    }
  }

  /**
   * Get the library.
   *
   * @param string $library
   *   The library.
   *
   * @return string
   *   The library.
   */
  private function getLibrary($library) {
    $definitions = $this->chartsManager->getDefinitions();
    if (!$library || $library === 'site_default') {
      $library = $this->chartSettings['library'] ?? key($definitions);
    }
    elseif (!isset($definitions[$library])) {
      $library = key($definitions);
    }

    return $library;
  }

  /**
   * Build the element.
   *
   * @param array $settings
   *   The settings.
   * @param string $chart_id
   *   The chart id.
   *
   * @return array
   *   The element.
   */
  public static function buildElement(array $settings, $chart_id) {
    $type = $settings['type'];
    $display_colors = $settings['display']['colors'] ?? [];

    // Overriding element colors for pie and donut chart types when the
    // settings display colors is empty.
    $overrides_element_colors = !$display_colors && ($type === 'pie' || $type === 'donut');

    $element = [
      '#type' => 'chart',
      '#chart_type' => $type,
      '#chart_library' => $settings['library'],
      '#title' => $settings['display']['title'],
      '#title_position' => $settings['display']['title_position'],
      '#tooltips' => $settings['display']['tooltips'],
      '#data_labels' => $settings['display']['data_labels'] ?? [],
      '#colors' => $display_colors,
      '#background' => $settings['display']['background'] ? $settings['display']['background'] : 'transparent',
      '#legend' => !empty($settings['display']['legend_position']),
      '#legend_position' => $settings['display']['legend_position'] ? $settings['display']['legend_position'] : '',
      '#width' => $settings['display']['dimensions']['width'],
      '#height' => $settings['display']['dimensions']['height'],
      '#width_units' => $settings['display']['dimensions']['width_units'],
      '#height_units' => $settings['display']['dimensions']['height_units'],
    ];

    if (!empty($settings['series'])) {
      $table = $settings['series'];
      // Extracting the categories.
      $categories = ChartDataCollectorTable::getCategoriesFromCollectedTable($table);
      // Extracting the rest of the data.
      $series_data = ChartDataCollectorTable::getSeriesFromCollectedTable($table, $type);

      $element['xaxis'] = [
        '#type' => 'chart_xaxis',
        '#labels' => $categories['data'],
        '#title' => $settings['xaxis']['title'] ? $settings['xaxis']['title'] : FALSE,
        '#labels_rotation' => $settings['xaxis']['labels_rotation'],
      ];

      if (!empty($series_data)) {

        $element['yaxis'] = [
          '#type' => 'chart_yaxis',
          '#title' => $settings['yaxis']['title'] ? $settings['yaxis']['title'] : '',
          '#labels_rotation' => $settings['yaxis']['labels_rotation'],
          '#max' => $settings['yaxis']['max'],
          '#min' => $settings['yaxis']['min'],
          '#prefix' => $settings['yaxis']['prefix'],
          '#suffix' => $settings['yaxis']['suffix'],
          '#decimal_count' => $settings['yaxis']['decimal_count'],
        ];

        // Create a secondary axis if needed.
        $series_count = count($series_data);
        if (!empty($settings['yaxis']['inherit']) && $series_count === 2) {
          $element['secondary_yaxis'] = [
            '#type' => 'chart_yaxis',
            '#title' => $settings['yaxis']['secondary']['title'] ? $settings['yaxis']['secondary']['title'] : '',
            '#labels_rotation' => $settings['yaxis']['secondary']['labels_rotation'],
            '#max' => $settings['yaxis']['secondary']['max'],
            '#min' => $settings['yaxis']['secondary']['min'],
            '#prefix' => $settings['yaxis']['secondary']['prefix'],
            '#suffix' => $settings['yaxis']['secondary']['suffix'],
            '#decimal_count' => $settings['yaxis']['secondary']['decimal_count'],
            '#opposite' => TRUE,
          ];
        }

        $series_counter = 0;
        // Overriding element colors for pie and donut chart types when the
        // settings display colors is empty.
        $overrides_element_colors = !$display_colors && ($type === 'pie' || $type === 'donut');
        foreach ($series_data as $data_index => $data) {
          $key = $chart_id . '_' . $data_index;
          $element[$key] = [
            '#type' => 'chart_data',
            '#data' => $data['data'],
            '#title' => $data['name'],
          ];
          if (!empty($data['color'])) {
            $element[$key]['#color'] = $data['color'];
            if ($overrides_element_colors) {
              $element['#colors'][] = $data['color'];
            }
          }
          if (isset($element['yaxis'])) {
            $element[$key]['#prefix'] = $settings['yaxis']['prefix'];
            $element[$key]['#suffix'] = $settings['yaxis']['suffix'];
            $element[$key]['#decimal_count'] = $settings['yaxis']['decimal_count'];
          }
          if (isset($element['secondary_yaxis']) && $series_counter === 1) {
            $element[$key]['#target_axis'] = 'secondary_yaxis';
            $element[$key]['#prefix'] = $settings['yaxis']['secondary']['prefix'];
            $element[$key]['#suffix'] = $settings['yaxis']['secondary']['suffix'];
            $element[$key]['#decimal_count'] = $settings['yaxis']['secondary']['decimal_count'];
          }
          $series_counter++;
        }
      }
      else {
        $element[$chart_id] = [
          '#type' => 'chart_data',
          '#title' => $series_data[0]['name'],
          '#data' => $series_data[0]['data'],
          '#prefix' => $settings['xaxis']['prefix'] ?? '',
          '#suffix' => $settings['xaxis']['suffix'] ?? '',
        ];
        if (!empty($series_data[0]['color'])) {
          $element[$chart_id]['#color'] = $series_data[0]['color'];
          if ($overrides_element_colors) {
            $element['#colors'][] = $series_data[0]['color'];
          }
        }
        $element['xaxis'] += [
          '#axis_type' => $settings['type'],
        ];
      }
    }

    return $element;
  }

}
