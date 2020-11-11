<?php

namespace Drupal\charts\Plugin\chart\Library;

use Drupal\charts\Settings\ChartsDefaultSettings;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class Chart plugins.
 */
abstract class ChartBase extends PluginBase implements ChartInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getChartName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Gets defaults settings.
   *
   * @return array
   *   The defaults settings.
   */
  public static function getDefaultSettings() {
    $defaults = [
      'type' => 'line',
      'library' => NULL,
      'grouping' => FALSE,
      'fields' => [
        'label' => NULL,
        'data_providers' => NULL,
      ],
      'display' => [
        'title' => '',
        'title_position' => 'out',
        'data_labels' => FALSE,
        'data_markers' => TRUE,
        'legend' => TRUE,
        'legend_position' => 'right',
        'background' => '',
        'three_dimensional' => FALSE,
        'polar' => FALSE,
        'tooltips' => TRUE,
        'tooltips_use_html' => FALSE,
        'dimensions' => [
          'width' => NULL,
          'width_units' => '%',
          'height' => NULL,
          'height_units' => 'px',
        ],
        'gauge' => [
          'green_to' => 100,
          'green_from' => 85,
          'yellow_to' => 85,
          'yellow_from' => 50,
          'red_to' => 50,
          'red_from' => 0,
          'max' => 100,
          'min' => 0,
        ],
        'colors' => self::getDefaultColors(),
      ],
    ];

    return $defaults;
  }

  /**
   * Gets the default hex colors.
   *
   * @return array
   *   The hex colors.
   */
  public static function getDefaultColors() {
    return [
      '#2f7ed8',
      '#0d233a',
      '#8bbc21',
      '#910000',
      '#1aadce',
      '#492970',
      '#f28f43',
      '#77a1e5',
      '#c42525',
      '#a6c96a',
    ];
  }

  /**
   * Gets options properties.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   The options.
   */
  protected function getOptionsFromElementProperties(array $element) {
    $options = [];
    $properties_mapping = ChartsDefaultSettings::getLegacySettingsMappingKeys();
    // Remove properties which don't have a mapping.
    $filtered_element = array_filter($element, function ($property) use ($properties_mapping) {
      $property = ltrim($property, '#');
      return isset($properties_mapping[$property]);
    });

    foreach ($filtered_element as $property => $value) {
      $property = ltrim($property, '#');
      $property_map = $properties_mapping[$property];

      if (substr($property_map, 0, 7) === 'display') {
        // Stripping the 'display_' in front of the mapping key.
        $property_map = substr($property_map, 8, strlen($property_map));
        if (substr($property_map, 0, 10) === 'dimensions') {
          // Stripping dimensions_.
          $property_map = substr($property_map, 11, strlen($property_map));
          $options['display']['dimensions'][$property_map] = $value;
        }
        elseif (substr($property_map, 0, 5) === 'gauge') {
          // Stripping gauge_.
          $property_map = substr($property_map, 6, strlen($property_map));
          $options['display']['gauge'][$property_map] = $value;
        }
        else {
          $options['display'][$property_map] = $value;
        }
      }
      elseif (substr($property_map, 0, 5) === 'xaxis') {
        // Stripping xaxis_.
        $property_map = substr($property_map, 6, strlen($property_map));
        $options['xaxis'][$property_map] = $value;
      }
      elseif (substr($property_map, 0, 5) === 'yaxis') {
        // Stripping yaxis_.
        $property_map = substr($property_map, 6, strlen($property_map));
        if (substr($property_map, 0, 9) === 'secondary') {
          // Stripping gauge_.
          $property_map = substr($property_map, 10, strlen($property_map));
          $options['yaxis']['secondary'][$property_map] = $value;
        }
        else {
          $options['yaxis'][$property_map] = $value;
        }
      }
      elseif (substr($property_map, 0, 6) === 'fields') {
        // Stripping fields_.
        $property_map = substr($property_map, 7, strlen($property_map));
        if ($property_map === 'data_providers' && is_array($value)) {
          $data_providers = !empty($options['fields']['data_providers']) ? $options['fields']['data_providers'] : [];
          if ($property === 'data_fields' || $property == 'field_colors') {
            $options['fields']['data_providers'] = ChartsDefaultSettings::getFieldsDataProviders($data_providers, $value);
          }
        }
        else {
          $options['fields'][$property_map] = $value;
        }
      }
      else {
        // We make sure that we handle the color unneeded array.
        $options[$property_map] = $property_map !== 'color' ? $value : $value[0];
      }
    }

    return $options;
  }

}
