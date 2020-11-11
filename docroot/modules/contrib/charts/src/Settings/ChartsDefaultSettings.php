<?php

namespace Drupal\charts\Settings;

use Drupal\Component\Utility\Color;
use Symfony\Component\Yaml\Yaml;

/**
 * The chart default settings instance.
 *
 *  @deprecated in charts:4.0.0-alpha1 and is removed from charts:4.0.0-alpha2.
 *   Use
 *   $config = \Drupal::config('charts.settings')['charts_default_settings'];
 *   instead.
 *   @see https://www.drupal.org/project/charts/issues/3167252
 */
class ChartsDefaultSettings {

  /**
   * The default colors.
   *
   * @var \Drupal\charts\Settings\ChartsDefaultColors
   */
  protected $colors;

  /**
   * The default settings.
   *
   * @var array
   */
  public $defaults;

  /**
   * ChartsDefaultSettings constructor.
   */
  public function __construct() {
    $this->colors = new ChartsDefaultColors();
    $this->defaults = $this->getSettingsFromDefaultConfig();
  }

  /**
   * Get the settings from the YAML file in the config/install directory.
   */
  private function getSettingsFromDefaultConfig() {
    $path = __DIR__ . '/../../config/install/charts.settings.yml';
    $default_config = Yaml::parse(file_get_contents($path))['charts_default_settings'];
    $defaults = [
      'type' => $default_config['type'],
      'library' => $default_config['library'],
      'grouping' => FALSE,
      'label_field' => NULL,
      'data_fields' => NULL,
      'field_colors' => NULL,
      'colors' => $default_config['display']['colors'],
      'title' => $default_config['display']['title'],
      'title_position' => $default_config['display']['title_position'],
      'data_labels' => $default_config['display']['data_labels'],
      'data_markers' => $default_config['display']['data_markers'],
      'legend' => $default_config['display']['legend'],
      'legend_position' => $default_config['display']['legend_position'],
      'background' => $default_config['display']['background'],
      'three_dimensional' => $default_config['display']['three_dimensional'],
      'polar' => $default_config['display']['polar'],
      'tooltips' => $default_config['display']['tooltips'],
      'tooltips_use_html' => $default_config['display']['tooltips_use_html'],
      'width' => $default_config['display']['dimensions']['width'],
      'width_units' => $default_config['display']['dimensions']['width_units'],
      'height' => $default_config['display']['dimensions']['height'],
      'height_units' => $default_config['display']['dimensions']['height_units'],
      'xaxis_title' => $default_config['xaxis']['title'],
      'xaxis_labels_rotation' => $default_config['xaxis']['labels_rotation'],
      'yaxis_title' => $default_config['yaxis']['title'],
      'yaxis_min' => $default_config['yaxis']['min'],
      'yaxis_max' => $default_config['yaxis']['max'],
      'yaxis_prefix' => $default_config['yaxis']['prefix'],
      'yaxis_suffix' => $default_config['yaxis']['suffix'],
      'yaxis_decimal_count' => $default_config['yaxis']['decimal_count'],
      'yaxis_labels_rotation' => $default_config['yaxis']['labels_rotation'],
      'green_to' => $default_config['display']['gauge']['green_to'],
      'green_from' => $default_config['display']['gauge']['green_from'],
      'yellow_to' => $default_config['display']['gauge']['yellow_to'],
      'yellow_from' => $default_config['display']['gauge']['yellow_from'],
      'red_to' => $default_config['display']['gauge']['red_to'],
      'red_from' => $default_config['display']['gauge']['red_from'],
      'max' => $default_config['display']['gauge']['max'],
      'min' => $default_config['display']['gauge']['min'],
    ];

    return $defaults;
  }

  /**
   * Gets defaults settings.
   *
   * @param bool $new_format
   *   Whether to return the new format or not.
   *
   * @return array
   *   The defaults settings.
   */
  public function getDefaults($new_format = FALSE) {
    $defaults = $this->defaults;

    // Transforming the legacy settings array to the newer one by making sure
    // that we don't do this process twice.
    if ($new_format && empty($defaults['display'])) {
      $keys_mapping = self::getLegacySettingsMappingKeys();
      $keys_mapping['colors'] = 'display_colors';
      $defaults = self::transformLegacySettingsToNew($defaults, $keys_mapping);
    }

    return $defaults;
  }

  /**
   * Sets the defaults settings.
   *
   * @param array $defaults
   *   The settings.
   */
  public function setDefaults(array $defaults) {
    $this->defaults = $defaults;
  }

  /**
   * Transforms legacy settings to newer ones.
   *
   * @param array $old_settings
   *   The old settings.
   * @param array $old_config_keys
   *   The old settings keys.
   *
   * @return array
   *   The new format settings.
   */
  public static function transformLegacySettingsToNew(array &$old_settings, array $old_config_keys = []) {
    $new_settings = [];
    $new_settings['fields']['stacking'] = !empty($old_settings['grouping']);
    $old_config_keys = $old_config_keys ?: self::getLegacySettingsMappingKeys();
    foreach ($old_settings as $setting_id => $setting_value) {
      $setting_key_map = isset($old_config_keys[$setting_id]) ? $old_config_keys[$setting_id] : '';
      if ($setting_key_map) {
        $value = self::transformBoolStringValueToBool($setting_value);
        // When a block setting belongs to the chart blocks we save it in a
        // new setting.
        if (substr($setting_key_map, 0, 7) === 'display') {
          // Stripping the 'display_' in front of the mapping key.
          $setting_key_map = substr($setting_key_map, 8, strlen($setting_key_map));
          if (substr($setting_key_map, 0, 10) === 'dimensions') {
            // Stripping dimensions_.
            $setting_key_map = substr($setting_key_map, 11, strlen($setting_key_map));
            $new_settings['display']['dimensions'][$setting_key_map] = $value;
          }
          elseif (substr($setting_key_map, 0, 5) === 'gauge') {
            // Stripping gauge_.
            $setting_key_map = substr($setting_key_map, 6, strlen($setting_key_map));
            $new_settings['display']['gauge'][$setting_key_map] = $value;
          }
          else {
            $new_settings['display'][$setting_key_map] = $value;
          }
        }
        elseif (substr($setting_key_map, 0, 5) === 'xaxis') {
          // Stripping xaxis_.
          $setting_key_map = substr($setting_key_map, 6, strlen($setting_key_map));
          $new_settings['xaxis'][$setting_key_map] = $value;
        }
        elseif (substr($setting_key_map, 0, 5) === 'yaxis') {
          // Stripping yaxis_.
          $setting_key_map = substr($setting_key_map, 6, strlen($setting_key_map));
          if (substr($setting_key_map, 0, 9) === 'secondary') {
            // Stripping gauge_.
            $setting_key_map = substr($setting_key_map, 10, strlen($setting_key_map));
            $new_settings['yaxis']['secondary'][$setting_key_map] = $value;
          }
          else {
            $new_settings['yaxis'][$setting_key_map] = $value;
          }
        }
        elseif (substr($setting_key_map, 0, 6) === 'fields') {
          // Stripping fields_.
          $setting_key_map = substr($setting_key_map, 7, strlen($setting_key_map));
          if ($setting_key_map === 'data_providers' && is_array($value)) {
            $data_providers = !empty($new_settings['fields']['data_providers']) ? $new_settings['fields']['data_providers'] : [];
            if ($setting_id === 'data_fields' || $setting_id == 'field_colors') {
              $new_settings['fields']['data_providers'] = self::getFieldsDataProviders($data_providers, $value);
            }
          }
          else {
            $new_settings['fields'][$setting_key_map] = $value;
          }
        }
        elseif ($setting_key_map === 'grouping' && $new_settings['fields']['stacking']) {
          $new_settings[$setting_key_map] = [];
        }
        else {
          // We make sure that we handle the color unneeded array.
          $new_settings[$setting_key_map] = $setting_key_map !== 'color' ? $value : $value[0];
        }
        // Then we remove it from the main old settings tree.
        unset($old_settings[$setting_id]);
      }
    }
    return $new_settings;
  }

  /**
   * Gets legacy settings mapping keys.
   *
   * @return array
   *   Legacy settings keys to newer ones mapping.
   */
  public static function getLegacySettingsMappingKeys() {
    return [
      'library' => 'library',
      'chart_library' => 'library',
      'type' => 'type',
      'chart_type' => 'type',
      'grouping' => 'grouping',
      'title' => 'display_title',
      'title_position' => 'display_title_position',
      'data_labels' => 'display_data_labels',
      'data_markers' => 'display_data_markers',
      'legend' => 'display_legend',
      'legend_position' => 'display_legend_position',
      'background' => 'display_background',
      'three_dimensional' => 'display_three_dimensional',
      'polar' => 'display_polar',
      'series' => 'series',
      'data' => 'data',
      'color' => 'color',
      'data_series' => 'data_series',
      'series_label' => 'series_label',
      'categories' => 'categories',
      'field_colors' => 'fields_data_providers',
      'tooltips' => 'display_tooltips',
      'tooltips_use_html' => 'display_tooltips_use_html',
      'width' => 'display_dimensions_width',
      'height' => 'display_dimensions_height',
      'width_units' => 'display_dimensions_width_units',
      'height_units' => 'display_dimensions_height_units',
      'xaxis_title' => 'xaxis_title',
      'xaxis_labels_rotation' => 'xaxis_labels_rotation',
      'yaxis_title' => 'yaxis_title',
      'yaxis_min' => 'yaxis_min',
      'yaxis_max' => 'yaxis_max',
      'yaxis_prefix' => 'yaxis_prefix',
      'yaxis_suffix' => 'yaxis_suffix',
      'yaxis_decimal_count' => 'yaxis_decimal_count',
      'yaxis_labels_rotation' => 'yaxis_labels_rotation',
      'inherit_yaxis' => 'yaxis_inherit',
      'secondary_yaxis_title' => 'yaxis_secondary_title',
      'secondary_yaxis_min' => 'yaxis_secondary_min',
      'secondary_yaxis_max' => 'yaxis_secondary_min',
      'secondary_yaxis_prefix' => 'yaxis_secondary_prefix',
      'secondary_yaxis_suffix' => 'yaxis_secondary_suffix',
      'secondary_yaxis_decimal_count' => 'yaxis_secondary_decimal_count',
      'secondary_yaxis_labels_rotation' => 'yaxis_secondary_labels_rotation',
      'green_from' => 'display_gauge_green_from',
      'green_to' => 'display_gauge_green_to',
      'red_from' => 'display_gauge_red_from',
      'red_to' => 'display_gauge_red_to',
      'yellow_from' => 'display_gauge_yellow_from',
      'yellow_to' => 'display_gauge_yellow_to',
      'max' => 'display_gauge_max',
      'min' => 'display_gauge_min',
      'allow_advanced_rendering' => 'fields_allow_advanced_rendering',
      'label_field' => 'fields_label',
      'data_fields' => 'fields_data_providers',
    ];
  }

  /**
   * Transform boolean strings value to real boolean.
   *
   * @param mixed $value
   *   The value to be transformed.
   *
   * @return bool|mixed
   *   The boolean value or the original passed value.
   */
  public static function transformBoolStringValueToBool($value) {
    if ($value === 'FALSE') {
      $value = FALSE;
    }
    elseif ($value === 'TRUE') {
      $value = TRUE;
    }

    return $value;
  }

  /**
   * Field data provider.
   *
   * @param array $data_providers
   *   Data providers.
   * @param array $legacy_value
   *   Legacy value.
   *
   * @return mixed
   *   Data providers returned
   */
  public static function getFieldsDataProviders(array $data_providers, array $legacy_value) {
    $default_weight = 0;
    foreach ($legacy_value as $field_id => $value) {
      if (Color::validateHex($value)) {
        $data_providers[$field_id]['color'] = $value;
      }
      else {
        $data_providers[$field_id]['enabled'] = !empty($value);
      }
      $data_providers[$field_id]['weight'] = $default_weight;
      $default_weight++;
    }
    return $data_providers;
  }

}
