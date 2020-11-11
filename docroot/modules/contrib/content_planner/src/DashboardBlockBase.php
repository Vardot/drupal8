<?php

namespace Drupal\content_planner;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Implements DashboardBlockBase.
 */
class DashboardBlockBase extends PluginBase implements DashboardBlockInterface, ContainerFactoryPluginInterface {

  use ConfigFormBaseTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UserLoginBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {

  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {

    if (array_key_exists('configurable', $this->pluginDefinition)) {
      return $this->pluginDefinition['configurable'];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Get basic configuration structure for the block configuration.
   *
   * @return array
   *   The basic configuration structure.
   */
  public static function getBasicConfigStructure() {

    return [
      'plugin_id' => NULL,
      'title' => NULL,
      'weight' => 0,
      'configured' => FALSE,
      'plugin_specific_config' => [],
    ];
  }

  /**
   * Get custom config.
   *
   * @param array $block_configuration
   *   The block plugin configuration.
   * @param string $key
   *   The config key.
   * @param mixed $default_value
   *   The default value to return if key does not exist in the specific
   *   configuration.
   *
   * @return mixed|null
   *   The config value or NULL.
   */
  protected function getCustomConfigByKey(array $block_configuration, $key, $default_value = NULL) {

    // If a given key exists in the plugin specific configuration, then return
    // it.
    if ((array_key_exists($key, $block_configuration['plugin_specific_config']))) {
      return $block_configuration['plugin_specific_config'][$key];
    }

    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigSpecificFormFields(FormStateInterface &$form_state, Request &$request, array $block_configuration) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface &$form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitSettingsForm(array &$form, FormStateInterface &$form_state) {}

}
