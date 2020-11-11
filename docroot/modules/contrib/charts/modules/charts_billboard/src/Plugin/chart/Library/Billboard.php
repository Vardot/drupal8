<?php

namespace Drupal\charts_billboard\Plugin\chart\Library;

use Drupal\charts\Plugin\chart\Library\ChartBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Define a concrete class for a Chart.
 *
 * @Chart(
 *   id = "billboard",
 *   name = @Translation("Billboard.js")
 * )
 */
class Billboard extends ChartBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['placeholder'] = [
      '#title' => $this->t('Placeholder'),
      '#type' => 'fieldset',
      '#description' => $this->t(
        'This is a placeholder for Billboard.js-specific library options. If you would like to help build this out, please work from <a href="@issue_link">this issue</a>.', [
          '@issue_link' => Url::fromUri('https://www.drupal.org/project/charts/issues/3046983')
            ->toString(),
        ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $element) {
    // Populate chart settings.
    $chart_definition = [];

    $chart_definition = $this->populateOptions($element, $chart_definition);
    $chart_definition = $this->populateData($element, $chart_definition);
    $chart_definition = $this->populateAxes($element, $chart_definition);

    if (!isset($element['#id'])) {
      $element['#id'] = Html::getUniqueId('chart-billboard');
    }
    $chart_definition['bindto'] = '#' . $element['#id'];

    $element['#attached']['library'][] = 'charts_billboard/billboard';
    $element['#attributes']['class'][] = 'charts-billboard charts-bb';
    $element['#chart_definition'] = $chart_definition;

    return $element;
  }

  /**
   * Get the chart type.
   *
   * @param string $chart_type
   *   The chart type.
   * @param bool $is_polar
   *   Whether the polar is checked.
   *
   * @return string
   *   The chart type.
   */
  protected function getType($chart_type, $is_polar = FALSE) {
    // If Polar is checked, then convert to Radar chart type.
    if ($is_polar) {
      $type = 'radar';
    }
    else {
      $type = $chart_type == 'column' ? 'bar' : $chart_type;
    }
    return $type;
  }

  /**
   * Get options.
   *
   * @param string $type
   *   The chart type.
   * @param array $element
   *   The element.
   *
   * @return array
   *   The returned options.
   */
  protected function getOptionsByType($type, array $element) {
    $options = $this->getOptionsByCustomProperty($element, $type);
    if ($type === 'bar') {
      $options['width'] = $element['#width'];
    }

    return $options;
  }

  /**
   * Get the options by custom property.
   *
   * @param array $element
   *   The element.
   * @param string $type
   *   The chart type.
   *
   * @return array
   *   The return options.
   */
  protected function getOptionsByCustomProperty(array $element, $type) {
    $options = [];
    $properties = Element::properties($element);
    // Remove properties which are not related to this chart type.
    $properties = array_filter($properties, function ($property) use ($type) {
      $query = '#chart_' . $type . '_';
      return substr($property, 0, strlen($query)) === $query;
    });
    foreach ($properties as $property) {
      $query = '#chart_' . $type . '_';
      $option_key = substr($property, strlen($query), strlen($property));
      $options[$option_key] = $element[$property];
    }
    return $options;
  }

  /**
   * Populate options.
   *
   * @param array $element
   *   The element.
   * @param array $chart_definition
   *   The chart definition.
   *
   * @return array
   *   Return the chart definition.
   */
  private function populateOptions(array $element, array $chart_definition) {
    $type = $this->getType($element['#chart_type']);
    $chart_definition['title']['text'] = $element['#title'] ? $element['#title'] : '';
    $chart_definition['legend']['show'] = !empty($element['#legend_position']);
    $chart_definition['axis']['x']['type'] = 'category';
    $chart_definition['data']['labels'] = (bool) $element['#data_labels'];

    if ($type === 'pie' || $type === 'donut') {

    }
    elseif ($type === 'line' || $type === 'spline') {
      $chart_definition['point']['show'] = !empty($element['#data_markers']);
    }
    else {
      /*
       * Billboard does not use bar, so column must be used. Since 'column'
       * is changed
       * to 'bar' in getType(), we need to use the value from the element.
       */
      if ($element['#chart_type'] === 'bar') {
        $chart_definition['axis']['rotated'] = TRUE;
      }
      elseif ($element['#chart_type'] === 'column') {
        $type = 'bar';
        $chart_definition['axis']['rotated'] = FALSE;
      }
    }
    $chart_definition['data']['type'] = $type;
    // Merge in chart raw options.
    if (!empty($element['#raw_options'])) {
      $chart_definition = NestedArray::mergeDeepArray([$element['#raw_options'], $chart_definition]);
    }

    return $chart_definition;
  }

  /**
   * Populate axes.
   *
   * @param array $element
   *   The element.
   * @param array $chart_definition
   *   The chart definition.
   *
   * @return array
   *   Return the chart definition.
   */
  private function populateAxes(array $element, array $chart_definition) {
    /** @var \Drupal\Core\Render\ElementInfoManagerInterface $element_info */
    $element_info = \Drupal::service('element_info');
    $children = Element::children($element);
    $axes = array_filter($children, function ($child) use ($element) {
      $type = $element[$child]['#type'];
      return $type === 'chart_xaxis' || $type === 'chart_yaxis';
    });
    // $series_data = array_filter($children, function ($child) use ($element) {
    // return $element[$child]['#type'] === 'chart_data';
    // });
    if ($axes) {
      foreach ($axes as $key) {
        // Make sure defaults are loaded.
        if (empty($element[$key]['#defaults_loaded'])) {
          $element[$key] += $element_info->getInfo($element[$key]['#type']);
        }
        $axis_type = $element[$key]['#type'] === 'chart_xaxis' ? 'x' : 'y';

        if ($axis_type === 'x') {
          $categories = $categories = array_map('strip_tags', $element[$key]['#labels']);
          $chart_definition['data']['columns'][] = ['x'];
          $chart_definition['data']['x'] = 'x';
          $categories_keys = array_keys($chart_definition['data']['columns']);
          $categories_key = end($categories_keys);
          foreach ($categories as $category) {
            $chart_definition['data']['columns'][$categories_key][] = $category;
          }
        }
      }
    }

    return $chart_definition;
  }

  /**
   * Populate data.
   *
   * @param array $element
   *   The element.
   * @param array $chart_definition
   *   The chart definition.
   *
   * @return array
   *   Return the chart definition.
   */
  private function populateData(array &$element, array $chart_definition) {
    $type = $this->getType($element['#chart_type']);
    $types = [];
    /** @var \Drupal\Core\Render\ElementInfoManagerInterface $element_info */
    $element_info = \Drupal::service('element_info');
    $children = Element::children($element);
    $children = array_filter($children, function ($child) use ($element) {
      return $element[$child]['#type'] === 'chart_data';
    });

    $columns = $chart_definition['data']['columns'] ?? [];
    $columns_key_start = $columns ? end(array_keys($columns)) + 1 : 0;
    foreach ($children as $key) {
      $child_element = $element[$key];
      // Make sure defaults are loaded.
      if (empty($child_element['#defaults_loaded'])) {
        $child_element += $element_info->getInfo($child_element['#type']);
      }
      if ($child_element['#color']) {
        $chart_definition['color']['pattern'][] = $child_element['#color'];
      }
      if (!in_array($type, ['pie', 'donut'])) {
        $series_title = strip_tags($child_element['#title']);
        $columns[$columns_key_start] = [$series_title];
        if ($type === 'scatter') {
          $columns[$columns_key_start + 1] = [$series_title . '_x'];
        }
        $types[$series_title] = $child_element['#chart_type'] ? $this->getType($child_element['#chart_type']) : $type;
        if ($type !== 'scatter') {
          foreach ($child_element['#data'] as $datum) {
            if (gettype($datum) === 'array') {
              $columns[$columns_key_start][] = array_map('strip_tags', $datum);
            }
            else {
              $columns[$columns_key_start][] = strip_tags($datum);
            }
          }
        }
        else {
          foreach ($child_element['#data'] as $datum) {
            $columns[$columns_key_start][] = $datum[0];
            $columns[$columns_key_start + 1][] = $datum[1];
          }
        }
      }
      else {
        foreach ($child_element['#data'] as $datum) {
          $columns[] = array_map('strip_tags', $datum);
        }
      }

      $columns_key_start++;
    }
    if ($element['#stacking']) {
      $chart_definition['data']['groups'] = [array_keys($types)];
    }
    $chart_definition['data']['types'] = $types;
    $chart_definition['data']['columns'] = $columns;

    return $chart_definition;
  }

}
