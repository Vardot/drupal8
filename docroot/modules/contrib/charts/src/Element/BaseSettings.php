<?php

namespace Drupal\charts\Element;

use Drupal\charts\Settings\ChartsDefaultSettings;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a form element for setting a chart.
 *
 * Properties:
 * - #used_in: Where the form is being used. basic_form is the default and other
 *   supported values are config_form for the main chart setting form and
 *   view_form for the view field form.
 * - #series: A boolean value. Set to TRUE when the usage require to collect
 *   chart series data.
 * - #field_options: properties mostly used by the view_form.
 *
 * Usage example:
 *
 * @code
 * $form['chart_config'] = [
 *   '#type' => 'charts_settings',
 *   '#title' => 'Charts configurations',
 *   '#used_in' => 'basic_form',
 * ];
 * @endcode
 *
 * @FormElement("charts_settings")
 */
class BaseSettings extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#default_value' => [],
      '#used_in' => 'basic_form',
      '#series' => FALSE,
      '#required' => FALSE,
      '#field_options' => [],
      '#library' => '',
      '#process' => [
        [$class, 'attachLibraryElementSubmit'],
        [$class, 'processSettings'],
        [$class, 'processGroup'],
      ],
      '#element_validate' => [
        [$class, 'validateLibraryPluginConfiguration'],
      ],
      '#charts_library_settings_element_submit' => [
        [$class, 'submitLibraryPluginConfiguration'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes the settings element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form.
   *
   * @return array
   *   The element.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function processSettings(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $supported_usage = ['basic_form', 'config_form', 'view_form'];
    if (empty($element['#used_in']) || !in_array($element['#used_in'], $supported_usage)) {
      throw new \InvalidArgumentException('The chart_base_settings element can only be used in basic, config and view forms.');
    }
    if (!is_array($element['#value'])) {
      throw new \InvalidArgumentException('The chart_base_settings #default_value must be an array.');
    }
    $parents = $element['#parents'];
    $id_prefix = implode('-', $parents);
    $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $value = $element['#value'];

    // Enforce tree.
    $element = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapper_id,
    ] + $element;
    $used_in = $element['#used_in'] ?: '';

    $required = !empty($element['#required']) ? $element['#required'] : FALSE;
    $options = $value ?? [];

    $library_options = [];
    if ($used_in !== 'config_form') {
      $library_options['site_default'] = new TranslatableMarkup('Site Default');
    }
    $library_options += self::getLibraries();

    if (!empty($element['#library']) && isset($library_options[$element['#library']])) {
      $element['library'] = [
        '#type' => 'value',
        '#value' => $element['#library'],
      ];
    }
    else {
      $element['library'] = [
        '#title' => new TranslatableMarkup('Charting library'),
        '#type' => 'select',
        '#options' => $library_options,
        '#default_value' => $options['library'],
        '#required' => $required,
        '#access' => count($library_options) > 0,
        '#attributes' => ['class' => ['chart-library-select']],
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
      ];
    }

    $element['type'] = [
      '#title' => new TranslatableMarkup('Chart type'),
      '#type' => 'radios',
      '#default_value' => $options['type'],
      '#options' => self::getChartTypes(),
      '#required' => $required,
      '#attributes' => [
        'class' => [
          'chart-type-radios',
          'container-inline',
        ],
      ],
    ];

    if (!empty($element['#series'])) {
      $element = self::processSeriesForm($element, $options, $form_state);
    }

    $element['display'] = [
      '#title' => new TranslatableMarkup('Display'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $element['display']['title'] = [
      '#title' => new TranslatableMarkup('Chart title'),
      '#type' => 'textfield',
      '#default_value' => $options['display']['title'],
    ];

    $element['xaxis'] = [
      '#title' => new TranslatableMarkup('Horizontal axis'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#attributes' => ['class' => ['chart-xaxis']],
    ];

    $element['yaxis'] = [
      '#title' => new TranslatableMarkup('Vertical axis'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#attributes' => ['class' => ['chart-yaxis']],
    ];

    if ($used_in === 'view_form') {
      $element = self::processViewForm($element, $options, $form_state);
    }
    elseif ($used_in === 'config_form') {
      $element = self::processConfigForm($element, $options);
    }

    $element['display']['title_position'] = [
      '#title' => new TranslatableMarkup('Title position'),
      '#type' => 'select',
      '#options' => [
        '' => new TranslatableMarkup('None'),
        'out' => new TranslatableMarkup('Outside'),
        'in' => new TranslatableMarkup('Inside'),
        'top' => new TranslatableMarkup('Top'),
        'right' => new TranslatableMarkup('Right'),
        'bottom' => new TranslatableMarkup('Bottom'),
        'left' => new TranslatableMarkup('Left'),
      ],
      '#description' => new TranslatableMarkup('Not all of these will apply to your selected library.'),
      '#default_value' => $options['display']['title_position'] ?? '',
    ];

    $element['display']['tooltips'] = [
      '#title' => new TranslatableMarkup('Enable tooltips'),
      '#type' => 'checkbox',
      '#description' => new TranslatableMarkup('Show data details on mouse over? Note: unavailable for print or on mobile devices.'),
      '#default_value' => !empty($options['display']['tooltips']),
    ];

    $element['display']['data_labels'] = [
      '#title' => new TranslatableMarkup('Enable data labels'),
      '#type' => 'checkbox',
      '#default_value' => !empty($options['display']['data_labels']),
      '#description' => new TranslatableMarkup('Show data details as labels on chart? Note: recommended for print or on mobile devices.'),
    ];

    $element['display']['data_markers'] = [
      '#title' => new TranslatableMarkup('Enable data markers'),
      '#type' => 'checkbox',
      '#default_value' => !empty($options['display']['data_markers']),
      '#description' => new TranslatableMarkup('Show data markers (points) on line charts?'),
    ];

    $element['display']['legend_position'] = [
      '#title' => new TranslatableMarkup('Legend position'),
      '#type' => 'select',
      '#options' => [
        '' => new TranslatableMarkup('None'),
        'top' => new TranslatableMarkup('Top'),
        'right' => new TranslatableMarkup('Right'),
        'bottom' => new TranslatableMarkup('Bottom'),
        'left' => new TranslatableMarkup('Left'),
      ],
      '#default_value' => $options['display']['legend_position'] ?? '',
    ];

    $element['display']['background'] = [
      '#title' => new TranslatableMarkup('Background color'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#attributes' => ['placeholder' => new TranslatableMarkup('transparent')],
      '#description' => new TranslatableMarkup('Leave blank for a transparent background.'),
      '#default_value' => $options['display']['background'] ?? '',
    ];

    $element['display']['three_dimensional'] = [
      '#title' => new TranslatableMarkup('Make chart three-dimensional (3D)'),
      '#type' => 'checkbox',
      '#default_value' => $options['display']['three_dimensional'] ?? FALSE,
      '#attributes' => [
        'class' => [
          'chart-type-checkbox',
          'container-inline',
        ],
      ],
    ];

    $element['display']['polar'] = [
      '#title' => new TranslatableMarkup('Transform cartesian charts into the polar coordinate system'),
      '#type' => 'checkbox',
      '#default_value' => $options['display']['polar'] ?? FALSE,
      '#attributes' => [
        'class' => [
          'chart-type-checkbox',
          'container-inline',
        ],
      ],
    ];

    $element['display']['dimensions'] = [
      '#title' => new TranslatableMarkup('Dimensions'),
      '#theme_wrappers' => ['form_element'],
      '#description' => new TranslatableMarkup('If dimensions are left empty, the chart will fill its containing element.'),
    ];

    $element['display']['dimensions']['width'] = [
      '#type' => 'number',
      '#attributes' => [
        'placeholder' => new TranslatableMarkup('auto'),
      ],
      '#min' => 0,
      '#max' => 9999,
      '#default_value' => $options['display']['dimensions']['width'] ?? '',
      '#size' => 8,
      '#theme_wrappers' => [],
    ];
    $element['display']['dimensions']['width_units'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => new TranslatableMarkup('%'),
      ],
      '#default_value' => $options['display']['dimensions']['width_units'] ?? '',
      '#suffix' => ' x ',
      '#size' => 2,
      '#theme_wrappers' => [],
    ];

    $element['display']['dimensions']['height'] = [
      '#type' => 'number',
      '#attributes' => [
        'placeholder' => new TranslatableMarkup('auto'),
      ],
      '#min' => 0,
      '#max' => 9999,
      '#default_value' => $options['display']['dimensions']['height'] ?? '',
      '#size' => 8,
      '#theme_wrappers' => [],
    ];
    $element['display']['dimensions']['height_units'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => new TranslatableMarkup('px'),
      ],
      '#default_value' => $options['display']['dimensions']['height_units'] ?? '',
      '#size' => 2,
      '#theme_wrappers' => [],
    ];

    $element['xaxis']['title'] = [
      '#title' => new TranslatableMarkup('Custom title'),
      '#type' => 'textfield',
      '#default_value' => $options['xaxis']['title'] ?? '',
    ];

    $element['xaxis']['labels_rotation'] = [
      '#title' => new TranslatableMarkup('Labels rotation'),
      '#type' => 'select',
      '#options' => [
        0 => new TranslatableMarkup('0°'),
        30 => new TranslatableMarkup('30°'),
        45 => new TranslatableMarkup('45°'),
        60 => new TranslatableMarkup('60°'),
        90 => new TranslatableMarkup('90°'),
      ],
      // This is only shown on non-inverted charts.
      '#attributes' => ['class' => ['axis-inverted-hide']],
      '#default_value' => $options['xaxis']['labels_rotation'] ?? '',
    ];

    $element['yaxis']['title'] = [
      '#title' => new TranslatableMarkup('Custom title'),
      '#type' => 'textfield',
      '#default_value' => $options['yaxis']['title'] ?? '',
    ];

    $element['yaxis']['min_max_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => new TranslatableMarkup('Value range'),
    ];
    $element['yaxis']['min'] = [
      '#type' => 'number',
      '#title' => new TranslatableMarkup('Value range minimum'),
      '#title_display' => 'invisible',
      '#attributes' => [
        'placeholder' => new TranslatableMarkup('Minimum'),
      ],
      '#max' => 999999999,
      '#default_value' => $options['yaxis']['min'] ?? '',
      '#size' => 12,
      '#suffix' => ' ',
    ];
    $element['yaxis']['max'] = [
      '#type' => 'number',
      '#attributes' => [
        'placeholder' => new TranslatableMarkup('Maximum'),
      ],
      '#max' => 999999999,
      '#default_value' => $options['yaxis']['max'] ?? '',
      '#size' => 12,
    ];

    $element['yaxis']['prefix'] = [
      '#title' => new TranslatableMarkup('Value prefix'),
      '#type' => 'textfield',
      '#default_value' => $options['yaxis']['prefix'] ?? '',
      '#size' => 12,
    ];
    $element['yaxis']['suffix'] = [
      '#title' => new TranslatableMarkup('Value suffix'),
      '#type' => 'textfield',
      '#default_value' => $options['yaxis']['suffix'] ?? '',
      '#size' => 12,
    ];

    $element['yaxis']['decimal_count'] = [
      '#title' => new TranslatableMarkup('Decimal count'),
      '#type' => 'number',
      '#attributes' => [
        'placeholder' => new TranslatableMarkup('auto'),
      ],
      '#min' => 0,
      '#max' => 20,
      '#default_value' => $options['yaxis']['decimal_count'] ?? '',
      '#size' => 5,
      '#description' => new TranslatableMarkup('Enforce a certain number of decimal-place digits in displayed values.'),
    ];

    $element['yaxis']['labels_rotation'] = [
      '#title' => new TranslatableMarkup('Labels rotation'),
      '#type' => 'select',
      '#options' => [
        0 => new TranslatableMarkup('0°'),
        30 => new TranslatableMarkup('30°'),
        45 => new TranslatableMarkup('45°'),
        60 => new TranslatableMarkup('60°'),
        90 => new TranslatableMarkup('90°'),
      ],
      // This is only shown on inverted charts.
      '#attributes' => ['class' => ['axis-inverted-show']],
      '#default_value' => $options['yaxis']['labels_rotation'] ?? '',
    ];

    // Adding basic form yaxis other fields.
    if ($used_in === 'basic_form') {
      $element = self::processBasicForm($element, $options);
    }

    // Settings for gauges.
    $element['display']['gauge'] = [
      '#title' => new TranslatableMarkup('Gauge settings'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#states' => [
        'visible' => [
          ':input[class*=chart-type-radios]' => ['value' => 'gauge'],
        ],
      ],
      'max' => [
        '#title' => new TranslatableMarkup('Gauge maximum value'),
        '#type' => 'number',
        '#default_value' => $options['display']['gauge']['max'] ?? '',
      ],
      'min' => [
        '#title' => new TranslatableMarkup('Gauge minimum value'),
        '#type' => 'number',
        '#default_value' => $options['display']['gauge']['min'] ?? '',
      ],
      'green_from' => [
        '#title' => new TranslatableMarkup('Green minimum value'),
        '#type' => 'number',
        '#default_value' => $options['display']['gauge']['green_from'] ?? '',
      ],
      'green_to' => [
        '#title' => new TranslatableMarkup('Green maximum value'),
        '#type' => 'number',
        '#default_value' => $options['display']['gauge']['green_to'] ?? '',
      ],
      'yellow_from' => [
        '#title' => new TranslatableMarkup('Yellow minimum value'),
        '#type' => 'number',
        '#default_value' => $options['display']['gauge']['yellow_from'] ?? '',
      ],
      'yellow_to' => [
        '#title' => new TranslatableMarkup('Yellow maximum value'),
        '#type' => 'number',
        '#default_value' => $options['display']['gauge']['yellow_to'] ?? '',
      ],
      'red_from' => [
        '#title' => new TranslatableMarkup('Red minimum value'),
        '#type' => 'number',
        '#default_value' => $options['display']['gauge']['red_from'] ?? '',
      ],
      'red_to' => [
        '#title' => new TranslatableMarkup('Red maximum value'),
        '#type' => 'number',
        '#default_value' => $options['display']['gauge']['red_to'] ?? '',
      ],
    ];

    if ($used_in === 'config_form' && !empty($options['library'])) {
      $element = self::buildLibraryConfigurationForm($element, $form_state, $options['library']);
    }

    return $element;
  }

  /**
   * Validates the chart library plugin configuration.
   *
   * @param array $element
   *   The chart base settings element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function validateLibraryPluginConfiguration(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $used_in = $element['#used_in'];
    if ($used_in === 'config_form') {
      $settings = $form_state->getValue($element['#parents']);
      // Adding validate callback for the chart library settings.
      if (!empty($settings['library'])) {
        $library = $settings['library'];
        $library_form = $library . '_settings';
        /** @var \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager */
        $plugin_manager = \Drupal::service('plugin.manager.charts');
        /** @var \Drupal\charts\Plugin\chart\Library\ChartInterface $plugin */
        $plugin = $plugin_manager->createInstance($library);
        $plugin->validateConfigurationForm($element[$library_form], $form_state);
      }
    }
  }

  /**
   * Submits the plugin configuration.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function submitLibraryPluginConfiguration(array &$element, FormStateInterface $form_state) {
    $used_in = $element['#used_in'];
    if ($used_in === 'config_form') {
      $settings = $form_state->getValue($element['#parents']);
      if (!empty($settings['library'])) {
        $library = $settings['library'];
        $library_form = $library . '_settings';
        /** @var \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager */
        $plugin_manager = \Drupal::service('plugin.manager.charts');
        /** @var \Drupal\charts\Plugin\chart\Library\ChartInterface $plugin */
        $plugin = $plugin_manager->createInstance($library);
        $plugin->submitConfigurationForm($element[$library_form], $form_state);
        $form_state->setValueForElement($element[$library_form], $plugin->getConfiguration());
      }
    }
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));
  }

  /**
   * Get the libraries.
   *
   * @return array
   *   The library options.
   */
  public static function getLibraries() {
    // Using plugins to get the available installed libraries.
    $plugin_manager = \Drupal::service('plugin.manager.charts');
    $plugin_definitions = $plugin_manager->getDefinitions();
    $library_options = [];

    foreach ($plugin_definitions as $plugin_definition) {
      $library_options[$plugin_definition['id']] = $plugin_definition['name'];
    }

    return $library_options;
  }

  /**
   * The types of chart.
   *
   * @return array
   *   The type options.
   */
  public static function getChartTypes() {
    $plugin_manager = \Drupal::service('plugin.manager.charts_type');
    $plugin_definitions = $plugin_manager->getDefinitions();
    $types_options = [];

    foreach ($plugin_definitions as $plugin_definition) {
      $types_options[$plugin_definition['id']] = $plugin_definition['label'];
    }
    return $types_options;
  }

  /**
   * The default setting.
   *
   * @deprecated in charts:4.0.0-alpha1 and is removed from charts:4.0.0-alpha2.
   *   Use
   *   $config = \Drupal::config('charts.settings')['charts_default_settings'];
   *   instead.
   *   @see https://www.drupal.org/project/charts/issues/3167252
   *
   * @return array
   *   Chart default settings.
   */
  public static function getDefaultSettings() {
    $settings = new ChartsDefaultSettings();
    return $settings->getDefaults();
  }

  /**
   * Attaches the #charts_library_settings_element_submit functionality.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed form element.
   */
  public static function attachLibraryElementSubmit(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (isset($complete_form['#charts_library_settings_element_submit_attached'])) {
      return $element;
    }
    // The #validate callbacks of the complete form run last.
    // That allows executeElementSubmitHandlers() to be completely certain that
    // the form has passed validation before proceeding.
    $complete_form['#validate'][] = [get_class(), 'executeLibraryElementSubmitHandlers'];
    $complete_form['#charts_library_settings_element_submit_attached'] = TRUE;

    return $element;
  }

  /**
   * Submits elements by calling their #charts_library_settings_element_submit.
   *
   * Callbacks.
   *
   * This approach was took from the commerce module to work around the fact.
   * that drupal core doesn't have an element_submit property.
   *
   * @param array &$form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function executeLibraryElementSubmitHandlers(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isSubmitted() || $form_state->hasAnyErrors()) {
      // The form wasn't submitted (#ajax in progress) or failed validation.
      return;
    }
    $triggering_element = $form_state->getTriggeringElement();
    $button_type = isset($triggering_element['#button_type']) ? $triggering_element['#button_type'] : '';
    if ($button_type != 'primary' && count($form_state->getButtons()) > 1) {
      // The form was submitted, but not via the primary button, which
      // indicates that it will probably be rebuilt.
      return;
    }

    self::doExecuteLibrarySubmitHandlers($form, $form_state);
  }

  /**
   * Calls the #charts_library_settings_element_submit callbacks recursively.
   *
   * @param array &$element
   *   The current element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function doExecuteLibrarySubmitHandlers(array &$element, FormStateInterface $form_state) {
    // Recurse through all children.
    foreach (Element::children($element) as $key) {
      if (!empty($element[$key])) {
        static::executeLibraryElementSubmitHandlers($element[$key], $form_state);
      }
    }

    // If there are callbacks on this level, run them.
    if (!empty($element['#charts_library_settings_element_submit'])) {
      foreach ($element['#charts_library_settings_element_submit'] as $callback) {
        call_user_func_array($callback, [&$element, &$form_state]);
      }
    }
  }

  /**
   * Process form.
   *
   * @param array $element
   *   The current element.
   * @param array $options
   *   Options.
   *
   * @return array
   *   The element.
   */
  private static function processBasicForm(array $element, array $options) {
    $element_name = $element['#name'];
    $element['yaxis']['inherit'] = [
      '#title' => new TranslatableMarkup('Add a secondary y-axis'),
      '#type' => 'checkbox',
      '#default_value' => $options['yaxis']['inherit'] ?? FALSE,
      '#description' => new TranslatableMarkup('Only one additional (secondary) y-axis can be created.'),
    ];

    $element['yaxis']['secondary'] = [
      '#title' => new TranslatableMarkup('Secondary vertical axis'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#attributes' => ['class' => ['chart-yaxis']],
      '#states' => [
        'visible' => [
          ':input[name="' . $element_name . '[yaxis][inherit]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['yaxis']['secondary']['title'] = [
      '#title' => new TranslatableMarkup('Custom title'),
      '#type' => 'textfield',
      '#default_value' => $options['yaxis']['secondary']['title'] ?? '',
    ];

    $element['yaxis']['secondary']['min_max_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => new TranslatableMarkup('Value range'),
    ];
    $element['yaxis']['secondary']['min'] = [
      '#type' => 'number',
      '#title' => new TranslatableMarkup('Value range minimum'),
      '#title_display' => 'invisible',
      '#attributes' => [
        'placeholder' => new TranslatableMarkup('Minimum'),
      ],
      '#max' => 999999999,
      '#size' => 12,
      '#suffix' => ' ',
      '#default_value' => $options['yaxis']['secondary']['min'] ?? '',
    ];
    $element['yaxis']['secondary']['max'] = [
      '#type' => 'number',
      '#title' => new TranslatableMarkup('Value range maximum'),
      '#title_display' => 'invisible',
      '#attributes' => [
        'placeholder' => new TranslatableMarkup('Maximum'),
      ],
      '#max' => 999999999,
      '#size' => 12,
      '#default_value' => $options['yaxis']['secondary']['max'] ?? '',
    ];

    $element['yaxis']['secondary']['prefix'] = [
      '#title' => new TranslatableMarkup('Value prefix'),
      '#type' => 'textfield',
      '#size' => 12,
      '#default_value' => $options['yaxis']['secondary']['prefix'] ?? '',
    ];

    $element['yaxis']['secondary']['suffix'] = [
      '#title' => new TranslatableMarkup('Value suffix'),
      '#type' => 'textfield',
      '#size' => 12,
      '#default_value' => $options['yaxis']['secondary']['suffix'] ?? '',
    ];

    $element['yaxis']['secondary']['decimal_count'] = [
      '#title' => new TranslatableMarkup('Decimal count'),
      '#type' => 'number',
      '#attributes' => [
        'placeholder' => new TranslatableMarkup('auto'),
      ],
      '#max' => 20,
      '#min' => 0,
      '#size' => 5,
      '#description' => new TranslatableMarkup('Enforce a certain number of decimal-place digits in displayed values.'),
      '#default_value' => $options['yaxis']['secondary']['decimal_count'] ?? '',
    ];

    $element['yaxis']['secondary']['labels_rotation'] = [
      '#title' => new TranslatableMarkup('Labels rotation'),
      '#type' => 'select',
      '#options' => [
        0 => new TranslatableMarkup('0°'),
        30 => new TranslatableMarkup('30°'),
        45 => new TranslatableMarkup('45°'),
        60 => new TranslatableMarkup('60°'),
        90 => new TranslatableMarkup('90°'),
      ],
      // This is only shown on inverted charts.
      '#attributes' => ['class' => ['axis-inverted-show']],
      '#default_value' => $options['yaxis']['secondary']['labels_rotation'] ?? '',
    ];

    return $element;
  }

  /**
   * Process view form.
   *
   * @param array $element
   *   The current element.
   * @param array $options
   *   The options.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The element.
   */
  private static function processViewForm(array $element, array $options, FormStateInterface $form_state) {
    if (!is_array($element['#field_options'])) {
      throw new \InvalidArgumentException('The chart_base_settings element need valid field options when used as view form.');
    }
    $element['display']['#weight'] = 2;
    $element['xaxis']['#weight'] = 2;
    $element['yaxis']['#weight'] = 2;

    $element_name = $element['#name'];
    $field_options = $element['#field_options'];
    $first_field = $field_options ? key($field_options) : '';

    $element['fields'] = [
      '#title' => new TranslatableMarkup('Charts fields'),
      '#type' => 'fieldset',
      '#weight' => 1,
    ];

    // Add a views-specific chart option to allow advanced rendering.
    // $element['fields']['allow_advanced_rendering'] = [
    // '#type' => 'checkbox',
    // '#title' => new TranslatableMarkup('Allow advanced rendering'),
    // '#description' => new TranslatableMarkup('Allow views field rewriting.
    // etc. for label and data fields. This can break charts if you rewrite
    // the field to a value the charting library cannot handle
    // - e.g. passing a string value into a numeric data column.'),
    // '#default_value' => isset($options['fields'].
    // ['allow_advanced_rendering']) ? $options['fields']
    // ['allow_advanced_rendering'] : NULL,].
    $element['fields']['label'] = [
      '#type' => 'radios',
      '#title' => new TranslatableMarkup('Label field'),
      '#options' => $field_options + ['' => new TranslatableMarkup('No label field')],
      '#default_value' => isset($options['fields']['label']) ? $options['fields']['label'] : $first_field,
    ];

    // Enable stacking.
    $element['fields']['stacking'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Stacking'),
      '#description' => new TranslatableMarkup('Enable stacking for this chart. Will stack based on the selected label field.'),
      '#default_value' => !empty($options['fields']['stacking']) ? $options['fields']['stacking'] : FALSE,
    ];

    $element['fields']['data_providers'] = [
      '#type' => 'table',
      '#header' => [
        new TranslatableMarkup('Field Name'),
        new TranslatableMarkup('Provides Data'),
        new TranslatableMarkup('Color'),
        new TranslatableMarkup('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'view-chart-fields-data-providers-order-weight',
        ],
      ],
    ];
    // Make the weight list always reflect the current number of values.
    // Taken from WidgetBase::formMultipleElements().
    $max_weight = count($field_options);

    foreach ($field_options as $field_name => $field_label) {
      $field_option_element = &$element['fields']['data_providers'][$field_name];
      $default_value = $options['fields']['data_providers'][$field_name] ?? [];
      $default_weight = $default_value['weight'] ?? $max_weight;

      $field_option_element['#attributes']['class'][] = 'draggable';
      // Field option label.
      $field_option_element['label'] = [
        '#markup' => new TranslatableMarkup('@label', [
          '@label' => $field_label,
        ]),
      ];

      $field_option_element['enabled'] = [
        '#type' => 'checkbox',
        '#title' => new TranslatableMarkup('Provides data'),
        '#title_display' => 'invisible',
        '#default_value' => !empty($default_value['enabled']),
        '#states' => [
          'disabled' => [
            ':input[name="' . $element_name . '[fields][label]"]' => ['value' => $field_name],
          ],
        ],
      ];

      $field_option_element['color'] = [
        '#type' => 'textfield',
        '#title' => new TranslatableMarkup('Color'),
        '#attributes' => ['TYPE' => 'color'],
        '#title_display' => 'invisible',
        '#size' => 10,
        '#maxlength' => 7,
        '#default_value' => $default_value['color'] ?? '#000000',
      ];

      $field_option_element['weight'] = [
        '#type' => 'weight',
        '#title' => new TranslatableMarkup('Weight'),
        '#title_display' => 'invisible',
        '#delta' => $max_weight,
        '#default_value' => $default_weight,
        '#attributes' => [
          'class' => ['view-chart-fields-data-providers-order-weight'],
        ],
      ];

      $field_option_element['#weight'] = $default_weight;
    }

    return $element;
  }

  /**
   * Process config form.
   *
   * @param array $element
   *   The current element.
   * @param array $options
   *   Options.
   *
   * @return array
   *   The element.
   */
  private static function processConfigForm(array $element, array $options) {
    $tab_group = implode('][', array_merge($element['#parents'], ['defaults']));
    $display_parents = array_merge($element['#parents'], ['display']);
    $element['defaults'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-' . implode('-', $display_parents),
    ];
    $element['display']['#type'] = 'details';
    $element['display']['#weight'] = 1;
    $element['display']['#group'] = $tab_group;

    $element['xaxis']['#type'] = 'details';
    $element['xaxis']['#weight'] = 2;
    $element['xaxis']['#group'] = $tab_group;

    $element['yaxis']['#type'] = 'details';
    $element['yaxis']['#weight'] = 3;
    $element['yaxis']['#group'] = $tab_group;

    $element['display']['colors'] = [
      '#title' => new TranslatableMarkup('Chart colors'),
      '#theme_wrappers' => ['form_element'],
      '#prefix' => '<div class="chart-colors">',
      '#suffix' => '</div>',
    ];

    for ($color_count = 0; $color_count < 10; $color_count++) {
      $element['display']['colors'][$color_count] = [
        '#type' => 'textfield',
        '#attributes' => ['TYPE' => 'color'],
        '#size' => 10,
        '#maxlength' => 7,
        '#theme_wrappers' => [],
        '#suffix' => ' ',
        '#default_value' => $options['display']['colors'][$color_count] ?? '',
      ];
    }

    return $element;
  }

  /**
   * Process series form.
   *
   * @param array $element
   *   The current element.
   * @param array $options
   *   The options.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The element.
   *
   * @throws \Exception
   */
  private static function processSeriesForm(array $element, array $options, FormStateInterface $form_state) {
    // Chart preview.
    $parents = $element['#parents'];
    $id_prefix = implode('-', $parents);
    $element_state = ChartDataCollectorTable::getElementState($parents, $form_state);

    if (!$element_state) {
      $element_state = $options;
      // Closing preview here cause this is probably initial form load.
      $open_preview = FALSE;
      $element_state[$id_prefix . '__open_preview'] = $open_preview;
      ChartDataCollectorTable::setElementState($parents, $form_state, $element_state);
    }
    else {
      $open_preview = $element_state[$id_prefix . '__open_preview'];
    }

    $wrapper_id = $element['#wrapper_id'] . '--preview';
    $element['preview'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Preview'),
      '#weight' => -99,
      '#open' => $open_preview,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];
    $element['preview']['submit'] = [
      '#type' => 'submit',
      '#value' => new TranslatableMarkup('Update Preview'),
      '#name' => $id_prefix . '--preview-submit',
      '#attributes' => [
        'class' => [Html::cleanCssIdentifier($id_prefix . '--preview-submit')],
      ],
      '#submit' => [[get_called_class(), 'chartPreviewSubmit']],
      '#limit_validation_errors' => [$parents],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefreshPreview'],
        'progress' => ['type' => 'throbber'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
      '#operation' => 'preview',
    ];

    $preview_content = new TranslatableMarkup('<p>Please fill up the required value below then update the preview.</p>');
    if (!empty($element_state['library']) && !empty($element_state['series'])) {
      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = \Drupal::service('renderer');
      $chart_build = Chart::buildElement($options, $wrapper_id);
      // @todo check if this would work with various hooks.
      $chart_build['#id'] = $wrapper_id;
      $chart_build['#chart_id'] = $id_prefix;

      $preview_content = $renderer->render($chart_build);
    }
    $element['preview']['content'] = [
      '#markup' => $preview_content,
    ];

    $element['series'] = [
      '#type' => 'chart_data_collector_table',
      '#initial_rows' => $element['#table_initial_rows'] ?? 5,
      '#initial_columns' => $element['#table_initial_columns'] ?? 2,
      '#table_drag' => FALSE,
      '#default_value' => $options['series'] ?? [],
    ];

    return $element;
  }

  /**
   * Preview refresh Ajax callback.
   */
  public static function ajaxRefreshPreview(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -2));
    return $element['preview'];
  }

  /**
   * Submit callback for the preview button.
   */
  public static function chartPreviewSubmit(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element_parents = array_slice($triggering_element['#parents'], 0, -2);
    $id_prefix = implode('-', $element_parents);

    // Getting the current element state.
    $element_state = ChartDataCollectorTable::getElementState($element_parents, $form_state);
    $element_state[$id_prefix . '__open_preview'] = TRUE;
    // Updating form state storage.
    ChartDataCollectorTable::setElementState($element_parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Builds the chart library configuration form into the settings.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $library
   *   The chart library.
   *
   * @return array
   *   The configuration subform.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private static function buildLibraryConfigurationForm(array $element, FormStateInterface $form_state, $library) {
    $library_form = $library . '_settings';
    $plugin_configuration = $element['#value'][$library_form] ?? [];
    // Using plugins to get the available installed libraries.
    /** @var \Drupal\charts\ChartManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.charts');
    /** @var \Drupal\charts\Plugin\chart\Library\ChartInterface $instance */
    $plugin = $plugin_manager->createInstance($library, $plugin_configuration);
    $element[$library_form] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('@library settings', [
        '@library' => $plugin->getPluginDefinition()['name'],
      ]),
      '#group' => $element['display']['#group'],
      '#weight' => 4,
    ];
    $element[$library_form] = $plugin->buildConfigurationForm($element[$library_form], $form_state);
    return $element;
  }

}
