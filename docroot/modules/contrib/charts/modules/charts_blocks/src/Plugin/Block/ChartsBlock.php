<?php

namespace Drupal\charts_blocks\Plugin\Block;

use Drupal\charts\Element\Chart;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\charts\Services\ChartsSettingsService;

/**
 * Provides a 'ChartsBlock' block.
 *
 * @Block(
 *  id = "charts_block",
 *  admin_label = @Translation("Charts block"),
 * )
 */
class ChartsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The charts default settings.
   *
   * @var array
   */
  protected $chartsDefaultSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ChartsSettingsService $chartsSettings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
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
      $container->get('charts.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);

    $chart_block_configurations = !empty($this->configuration['chart']) ? $this->configuration['chart'] : [];
    if (!empty($this->chartsDefaultSettings)) {
      // Get the charts default settings.
      $default_options = $this->chartsDefaultSettings;
      // Merge the charts default settings with this block's configuration.
      $defaults = NestedArray::mergeDeep($default_options, $chart_block_configurations);
    }
    else {
      $defaults = $chart_block_configurations;
    }

    $form['chart'] = [
      '#type' => 'details',
      '#title' => $this->t('Chart configurations'),
      '#open' => TRUE,
    ];

    $form['chart']['settings'] = [
      '#type' => 'charts_settings',
      '#used_in' => 'basic_form',
      '#required' => TRUE,
      '#series' => TRUE,
      '#default_value' => $defaults,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['chart'] = $form_state->getValue(['chart', 'settings']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $chart_settings = !empty($this->configuration['chart']) ? $this->configuration['chart'] : [];

    // Creates a UUID for the chart ID.
    $chart_id = 'charts_block__' . $this->configuration['id'];
    $uuid_service = \Drupal::service('uuid');
    $id = 'chart-' . $uuid_service->generate();
    $build = Chart::buildElement($chart_settings, $chart_id);
    $build['#id'] = $id;
    $build['#chart_id'] = $chart_id;

    return $build;
  }

}
