<?php

namespace Drupal\charts_api_example\Controller;

use Drupal\charts\Services\ChartsSettingsServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Uuid\UuidInterface;

/**
 * Charts Api Example.
 */
class ChartsApiExample extends ControllerBase {

  /**
   * The charts settings.
   *
   * @var \Drupal\charts\Services\ChartsSettingsServiceInterface
   */
  protected $chartSettings;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Construct.
   *
   * @param \Drupal\charts\Services\ChartsSettingsServiceInterface $chartSettings
   *   The charts settings.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuidService
   *   The UUID service.
   */
  public function __construct(ChartsSettingsServiceInterface $chartSettings, MessengerInterface $messenger, UuidInterface $uuidService) {
    $this->chartSettings = $chartSettings->getChartsSettings();
    $this->messenger = $messenger;
    $this->uuidService = $uuidService;
  }

  /**
   * Display.
   *
   * @return array
   *   Array to render.
   */
  public function display() {

    $library = $this->chartSettings['library'];
    if (empty($library)) {
      $this->messenger->addError($this->t('You need to first configure Charts default settings'));
      return [];
    }

    // If you want to include raw options, you can do so like this.
    // $options = [
    // 'chart' => [
    // 'backgroundColor' => '#000000'
    // ]
    // ];.
    $build = [
      '#type' => 'chart',
      '#chart_type' => $this->chartSettings['type'],
      '#title' => $this->t('Chart title'),
      '#title_position' => 'out',
      '#tooltips' => $this->chartSettings['display']['tooltips'],
      '#data_labels' => $this->chartSettings['data_labels'] ?? '',
      '#colors' => $this->chartSettings['display']['colors'],
      '#background' => $this->chartSettings['display']['background'] ? $this->chartSettings['display']['background'] : 'transparent',
      '#legend' => !empty($this->chartSettings['display']['legend_position']),
      '#legend_position' => $this->chartSettings['display']['legend_position'] ? $this->chartSettings['display']['legend_position'] : '',
      '#width' => $this->chartSettings['display']['dimensions']['width'],
      '#height' => $this->chartSettings['display']['dimensions']['height'],
      '#width_units' => $this->chartSettings['display']['dimensions']['width_units'],
      '#height_units' => $this->chartSettings['display']['dimensions']['height_units'],
     // '#raw_options' => $options,
      '#chart_id' => 'foobar',
    ];

    $categories = ['Category 1', 'Category 2', 'Category 3', 'Category 4'];

    $build['xaxis'] = [
      '#type' => 'chart_xaxis',
      '#labels' => $categories,
      '#title' => $this->chartSettings['xaxis']['title'] ? $this->chartSettings['xaxis']['title'] : FALSE,
      '#labels_rotation' => $this->chartSettings['xaxis']['labels_rotation'],
      '#axis_type' => $this->chartSettings['type'],
    ];

    $build['yaxis'] = [
      '#type' => 'chart_yaxis',
      '#title' => $this->chartSettings['yaxis']['title'] ? $this->chartSettings['yaxis']['title'] : '',
      '#labels_rotation' => $this->chartSettings['yaxis']['labels_rotation'],
      '#max' => $this->chartSettings['yaxis']['max'],
      '#min' => $this->chartSettings['yaxis']['min'],
    ];

    $i = 0;
    $build[$i] = [
      '#type' => 'chart_data',
      '#data' => [250, 350, 400, 200],
      '#title' => 'Series 1',
    ];

    // Sample data format.
    $seriesData[] = [
      'name' => 'Series 1',
      'color' => '#0d233a',
      'type' => $this->chartSettings['type'],
      'data' => [250, 350, 400, 200],
    ];
    switch ($this->chartSettings['type']) {
      default:
        $seriesData[] = [
          'name' => 'Series 2',
          'color' => '#8bbc21',
          'type' => 'line',
          'data' => [150, 450, 500, 300],
        ];
        $seriesData[] = [
          'name' => 'Series 3',
          'color' => '#910000',
          'type' => 'area',
          'data' => [0, 0, 60, 90],
        ];
      case 'pie':
      case 'donut':

    }

    foreach ($seriesData as $index => $data) {
      $build[$index] = [
        '#type' => 'chart_data',
        '#data' => $data['data'],
        '#title' => $data['name'],
        '#color' => $data['color'],
        '#chart_type' => $data['type'],
      ];
    }

    // Creates a UUID for the chart ID.
    $chartId = 'chart-' . $this->uuidService->generate();

    $build['#id'] = $chartId;

    return $build;
  }

  /**
   * Display Two.
   *
   * @return array
   *   Array to render.
   */
  public function displayTwo() {

    $chart = [];
    $chart['example_one'] = [
      '#type' => 'chart',
      '#chart_type' => 'column',
    ];
    $chart['example_one']['male'] = [
      '#type' => 'chart_data',
      '#title' => $this->t('Male'),
      '#data' => [10, 20, 30],
    ];
    $chart['example_one']['xaxis'] = [
      '#type' => 'chart_xaxis',
      '#title' => $this->t('Month'),
      '#labels' => [$this->t('Jan'), $this->t('Feb'), $this->t('Mar')],
    ];

    return $chart;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('charts.settings'),
      $container->get('messenger'),
      $container->get('uuid')
    );
  }

}
