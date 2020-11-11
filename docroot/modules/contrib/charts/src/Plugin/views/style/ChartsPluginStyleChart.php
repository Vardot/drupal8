<?php

namespace Drupal\charts\Plugin\views\style;

use Drupal\charts\Plugin\chart\Library\ChartInterface;
use Drupal\charts\Services\ChartAttachmentServiceInterface;
use Drupal\charts\ChartManager;
use Drupal\charts\Services\ChartsSettingsService;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\core\form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Style plugin to render view as a chart.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "chart",
 *   title = @Translation("Chart"),
 *   help = @Translation("Render a chart of your data."),
 *   theme = "views_view_charts",
 *   display_types = { "normal" }
 * )
 */
class ChartsPluginStyleChart extends StylePluginBase implements ContainerFactoryPluginInterface {

  /**
   * Fields.
   *
   * @var usesFields
   */
  protected $usesFields = TRUE;

  /**
   * RowPlugin.
   *
   * @var usesRowPlugin
   */
  protected $usesRowPlugin = TRUE;

  /**
   * The attachment service.
   *
   * @var \Drupal\charts\Services\ChartAttachmentService
   */
  protected $attachmentService;

  /**
   * The chart manager service.
   *
   * @var \Drupal\charts\ChartManager
   */
  protected $chartManager;

  /**
   * The module handler.
   *
   * @var moduleHandler
   */
  protected $moduleHandler;

  /**
   * The default settings.
   *
   * @var chartsDefaultSettings
   */
  protected $chartsDefaultSettings;

  /**
   * The setting form for chart.
   *
   * @var chartsBaseSettingsForm
   */
  protected $chartsBaseSettingsForm;

  /**
   * Constructs a ChartsPluginStyleChart object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\charts\Services\ChartAttachmentServiceInterface $attachment_service
   *   The attachment service.
   * @param \Drupal\charts\ChartManager $chart_manager
   *   The chart manager service.
   * @param \Drupal\charts\Services\ChartsSettingsService $chartsSettings
   *   The chart setting form.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ChartAttachmentServiceInterface $attachment_service,
    ChartManager $chart_manager,
    ChartsSettingsService $chartsSettings
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->attachmentService = $attachment_service;
    $this->chartManager = $chart_manager;
    $this->chartsDefaultSettings = $chartsSettings->getChartsSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('charts.charts_attachment'),
      $container->get('plugin.manager.charts'),
      $container->get('charts.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['chart_settings'] = [
      'default' => $this->chartsDefaultSettings ?: [],
    ];
    $options['chart_settings']['fields']['allow_advanced_rendering'] = FALSE;

    $options['chart_settings']['library'] = '';

    // @todo: ensure that chart extensions inherit defaults from parent
    // Remove the default setting for chart type so it can be inherited if this
    // is a chart extension type.
    if ($this->view->style_plugin === 'chart_extension') {
      $options['chart_settings']['default']['type'] = NULL;
    }
    $options['path'] = ['default' => 'charts'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $handlers = $this->displayHandler->getHandlers('field');
    if (empty($handlers)) {
      $form['error_markup'] = ['#markup' => '<div class="error messages">' . $this->t('You need at least one field before you can configure your table settings') . '</div>'];
      return;
    }

    // Limit grouping options (we only support one grouping field).
    if (isset($form['grouping'][0])) {
      $form['grouping'][0]['field']['#title'] = $this->t('Grouping field');
      $form['grouping'][0]['field']['#description'] = $this->t('If grouping by a particular field, that field will be used to determine stacking of the chart. Generally this will be the same field as what you select for the "Label field" below. If you do not have more than one "Provides data" field below, there will be nothing to stack. If you want to have another series displayed, use a "Chart attachment" display, and set it to attach to this display.');
      $form['grouping'][0]['field']['#attributes']['class'][] = 'charts-grouping-field';
      // Grouping by rendered version has no effect in charts. Hide the options.
      $form['grouping'][0]['rendered']['#access'] = FALSE;
      $form['grouping'][0]['rendered_strip']['#access'] = FALSE;
    }
    if (isset($form['grouping'][1])) {
      $form['grouping'][1]['#access'] = FALSE;
    }

    // Merge in the global chart settings form.
    $field_options = $this->displayHandler->getFieldLabels();
    $form_state->set('default_options', $this->options);
    $form['chart_settings'] = [
      '#type' => 'charts_settings',
      '#used_in' => 'view_form',
      '#required' => TRUE,
      '#field_options' => $field_options,
      '#default_value' => $this->options['chart_settings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $chart_settings = $this->options['chart_settings'];
    $selected_data_fields = is_array($chart_settings['fields']['data_providers']) ? $this->getSelectedDataFields($chart_settings['fields']['data_providers']) : NULL;

    // Avoid calling validation before arriving on the view edit page.
    if (\Drupal::routeMatch()->getRouteName() != 'views_ui.add' && empty($selected_data_fields)) {
      $errors[] = $this->t('At least one data field must be selected in the chart configuration before this chart may be shown');
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $field_handlers = $this->view->getHandlers('field');
    $chart_settings = $this->options['chart_settings'];
    $chart_fields = $chart_settings['fields'];

    // Calculate the labels field alias.
    $label_field = isset($field_handlers[$chart_fields['label']]) ? $field_handlers[$chart_fields['label']] : '';
    $label_field_key = $label_field ? $chart_fields['label'] : '';

    // Assemble the fields to be used to provide data access.
    $field_keys = array_keys($this->getSelectedDataFields($chart_fields['data_providers']));
    $data_fields = array_filter($field_handlers, function ($field_handler) use ($field_keys, $label_field_key) {
      if (isset($field_handler['exclude']) && ($field_handler['exclude'] == FALSE || $field_handler['exclude'] == 0)) {
        $field_id = $field_handler['id'];
      }
      else {
        $field_id = '';
      }
      // Do not allow the label field to be used as a data field.
      return $field_id !== $label_field_key && in_array($field_id, $field_keys);
    });

    // Allow argument tokens in the title.
    $title = !empty($chart_settings['display']['title']) ? $chart_settings['display']['title'] : '';
    if (!empty($this->view->build_info['substitutions'])) {
      $tokens = $this->view->build_info['substitutions'];
      $title = $this->viewsTokenReplace($title, $tokens);
    }

    // To be used with the exposed chart type field.
    if ($this->view->storage->get('exposed_chart_type')) {
      $chart_settings['type'] = $this->view->storage->get('exposed_chart_type');
    }

    $chart_id = $this->view->id() . '_' . $this->view->current_display;
    $chart = [
      '#type' => 'chart',
      '#chart_type' => $chart_settings['type'],
      '#chart_library' => $chart_settings['library'],
      '#chart_id' => $chart_id,
      '#id' => Html::getUniqueId('chart_' . $chart_id),
      '#stacking' => $chart_settings['fields']['stacking'] ?? '0',
      '#polar' => $chart_settings['display']['polar'],
      '#three_dimensional' => $chart_settings['display']['three_dimensional'],
      '#title' => $title,
      '#title_position' => $chart_settings['display']['title_position'],
      '#tooltips' => $chart_settings['display']['tooltips'],
      '#data_labels' => $chart_settings['display']['data_labels'],
      // Colors only used if a grouped view or using a type such as a pie chart.
      '#colors' => $chart_settings['display']['colors'] ?? [],
      '#background' => $chart_settings['display']['background'] ? $chart_settings['display']['background'] : 'transparent',
      '#legend' => !empty($chart_settings['display']['legend_position']),
      '#legend_position' => $chart_settings['display']['legend_position'] ? $chart_settings['display']['legend_position'] : '',
      '#width' => $chart_settings['display']['dimensions']['width'],
      '#height' => $chart_settings['display']['dimensions']['height'],
      '#width_units' => $chart_settings['display']['dimensions']['width_units'],
      '#height_units' => $chart_settings['display']['dimensions']['height_units'],
      '#attributes' => ['data-drupal-selector-chart' => Html::getId($chart_id)],
      // Pass info about the actual view results to allow further processing.
      '#view' => $this->view,
    ];

    /** @var \Drupal\charts\TypeManager $chart_type_plugin_manager */
    $chart_type_plugin_manager = \Drupal::service('plugin.manager.charts_type');
    $chart_type = $chart_type_plugin_manager->getDefinition($chart_settings['type']);
    if ($chart_type['axis'] === ChartInterface::SINGLE_AXIS) {
      $data_field_key = key($data_fields);
      $data_field = $data_fields[$data_field_key];
      $data = [];
      $this->renderFields($this->view->result);
      $renders = $this->rendered_fields;
      foreach ($renders as $row_number => $row) {
        $data_row = [];
        if ($label_field_key) {
          // Labels need to be decoded, as the charting library will re-encode.
          $data_row[] = strip_tags($this->getField($row_number, $label_field_key), ENT_QUOTES);
        }
        $data_row[] = $this->processNumberValueFromField($row_number, $data_field_key);
        $data[] = $data_row;
      }

      // @todo: create a textfield for chart legend title.
      // if ($chart_fields['label']) {
      // $chart['#legend_title'] = $chart_fields['label'];
      // }
      $chart[$this->view->current_display . '_series'] = [
        '#type' => 'chart_data',
        '#data' => $data,
        '#title' => $data_field['label'],
        '#color' => isset($chart_fields['data_providers'][$data_field_key]) ? $chart_fields['data_providers'][$data_field_key]['color'] : '',
      ];

    }
    else {
      $chart['xaxis'] = [
        '#type' => 'chart_xaxis',
        '#title' => $chart_settings['xaxis']['title'] ? $chart_settings['xaxis']['title'] : FALSE,
        '#labels_rotation' => $chart_settings['xaxis']['labels_rotation'],
      ];
      $chart['yaxis'] = [
        '#type' => 'chart_yaxis',
        '#title' => $chart_settings['yaxis']['title'] ? $chart_settings['yaxis']['title'] : '',
        '#labels_rotation' => $chart_settings['yaxis']['labels_rotation'],
        '#max' => $chart_settings['yaxis']['max'],
        '#min' => $chart_settings['yaxis']['min'],
      ];

      $sets = $this->renderGrouping($this->view->result, $this->options['grouping'], TRUE);
      $series_index = 0;
      foreach ($sets as $series_label => $data_set) {
        foreach ($data_fields as $field_key => $field_handler) {
          $element_key = $this->view->current_display . '__' . $field_key . '_' . $series_index;
          $chart[$element_key] = [
            '#type' => 'chart_data',
            '#data' => [],
            // If using a grouping field, inherit from the chart level colors.
            '#color' => ($series_label === '' && isset($chart_fields['data_providers'][$field_key])) ? $chart_fields['data_providers'][$field_key]['color'] : '',
            '#title' => $series_label ? strip_tags($series_label) : $field_handler['label'],
            '#prefix' => $chart_settings['yaxis']['prefix'] ? $chart_settings['yaxis']['prefix'] : NULL,
            '#suffix' => $chart_settings['yaxis']['suffix'] ? $chart_settings['yaxis']['suffix'] : NULL,
            '#decimal_count' => $chart_settings['yaxis']['decimal_count'] ? $chart_settings['yaxis']['decimal_count'] : '',
          ];
        }

        // Grouped results come back indexed by their original result number
        // from before the grouping, so we need to keep our own row number when
        // looping through the rows.
        $row_number = 0;
        foreach ($data_set['rows'] as $result_number => $row) {
          if ($label_field_key && !isset($chart['xaxis']['#labels'][$row_number])) {
            $chart['xaxis']['#labels'][$row_number] = strip_tags($this->getField($row_number, $label_field_key));
          }
          foreach ($data_fields as $field_key => $field_handler) {
            // Don't allow the grouping field to provide data.
            if (isset($this->options['grouping'][0]['field']) && $field_key === $this->options['grouping'][0]['field']) {
              continue;
            }
            $element_key = $this->view->current_display . '__' . $field_key . '_' . $series_index;
            $value = $this->processNumberValueFromField($result_number, $field_key);
            $chart[$element_key]['#data'][] = $value;
          }
          $row_number++;
        }

        // Incrementing series index.
        $series_index++;
      }
    }

    // Check if this display has any children charts that should be applied
    // on top of it.
    $children_displays = $this->getChildrenChartDisplays();
    // Contains the different subviews of the attachments.
    $attachments = [];

    foreach ($children_displays as $child_display) {
      // If the user doesn't have access to the child display, skip.
      if (!$this->view->access($child_display)) {
        continue;
      }

      // Generate the subchart by executing the child display. We load a fresh
      // view here to avoid collisions in shifting the current display while in
      // a display.
      $subview = $this->view->createDuplicate();
      $subview->setDisplay($child_display);
      $child_display_handler = $this->view->displayHandlers->get($child_display);
      $child_display_settings = $subview->display_handler->options['style']['options']['chart_settings'];

      // Copy the settings for our axes over to the child view.
      foreach ($chart_settings as $option_name => $option_value) {
        if ($child_display_handler->options['inherit_yaxis'] === '1') {
          $child_display_settings[$option_name] = $option_value;
        }
      }

      // Set the arguments on the subview if it is configured to inherit;
      // arguments.
      if (!empty($child_display_handler->display['display_options']['inherit_arguments']) && $child_display_handler->display['display_options']['inherit_arguments'] == '1') {
        $subview->setArguments($this->view->args);
      }

      // Execute the subview and get the result.
      $subview->preExecute();
      $subview->execute();

      // If there's no results, don't attach the subview.
      if (empty($subview->result)) {
        continue;
      }

      $subchart = $subview->style_plugin->render();
      // Add attachment views to attachments array.
      array_push($attachments, $subview);

      // Create a secondary axis if needed.
      if ($child_display_handler->options['inherit_yaxis'] !== '1' && isset($subchart['yaxis'])) {
        $chart['secondary_yaxis'] = $subchart['yaxis'];
        $chart['secondary_yaxis']['#opposite'] = TRUE;
      }

      // Merge in the child chart data.
      foreach (Element::children($subchart) as $key) {
        if ($subchart[$key]['#type'] === 'chart_data') {
          $chart[$key] = $subchart[$key];
          // If the subchart is a different type than the parent chart, set
          // the #chart_type property on the individual chart data elements.
          if ($subchart['#chart_type'] !== $chart['#chart_type']) {
            $chart[$key]['#chart_type'] = $subchart['#chart_type'];
          }
          if ($child_display_handler->options['inherit_yaxis'] !== '1') {
            $chart[$key]['#target_axis'] = 'secondary_yaxis';
          }
        }
      }
    }
    $this->attachmentService->setAttachmentViews($attachments);

    // Print the chart.
    return $chart;
  }

  /**
   * Utility function to check if this chart has a parent display.
   *
   * @return bool
   *   Parent Display.
   */
  public function getParentChartDisplay() {
    $parent_display = FALSE;

    return $parent_display;
  }

  /**
   * Utility function to check if this chart has children displays.
   *
   * @return array
   *   Children Chart Display.
   */
  public function getChildrenChartDisplays() {
    $children_displays = $this->displayHandler->getAttachedDisplays();
    foreach ($children_displays as $key => $child) {
      $display_handler = $this->view->displayHandlers->get($child);
      // Unset disabled & non chart attachments.
      if ((!$display_handler->isEnabled()) || (strstr($child, 'chart_extension') == !TRUE)) {
        unset($children_displays[$key]);
      }
    }
    $children_displays = array_values($children_displays);

    return $children_displays;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];

    if (!empty($this->options['library'])) {
      $plugin_definition = $this->chartManager->getDefinition($this->options['library']);
      $dependencies['module'] = [$plugin_definition['provider']];
    }

    return $dependencies;
  }

  /**
   * Utility method to filter out unselected fields from data providers fields.
   *
   * @param array $data_providers
   *   The data providers.
   *
   * @return array
   *   The fields.
   */
  private function getSelectedDataFields(array $data_providers) {
    return array_filter($data_providers, function ($value) {

      return !empty($value['enabled']);
    });
  }

  /**
   * Processes number value based on field.
   *
   * @param int $number
   *   The number.
   * @param string $field
   *   The field.
   *
   * @return \Drupal\Component\Render\MarkupInterface|float|null
   *   The value.
   */
  private function processNumberValueFromField($number, $field) {
    $value = current($this->getField($number, $field));
    if (\Drupal::service('twig')->isDebug()) {
      $value = trim(strip_tags($value));
    }
    if (strpos($field, 'field_charts_fields_scatter') === 0) {

      return Json::decode($value);
    }
    // Convert empty strings to NULL.
    if ($value === '' || is_null($value)) {
      $value = NULL;
    }
    else {
      // Strip thousands placeholders if present, then cast to float.
      $value = (float) str_replace([',', ' '], '', $value);
    }

    return $value;
  }

}
