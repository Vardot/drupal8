<?php

namespace Drupal\charts\Services;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Charts Settings Service.
 */
class ChartsSettingsService implements ChartsSettingsServiceInterface {

  /**
   * The factory configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getChartsSettings() {
    $config = $this->configFactory->getEditable('charts.settings');

    return $config->get('charts_default_settings');
  }

}
