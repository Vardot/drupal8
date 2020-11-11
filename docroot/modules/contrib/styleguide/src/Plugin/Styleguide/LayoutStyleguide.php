<?php

namespace Drupal\styleguide\Plugin\Styleguide;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\styleguide\GeneratorInterface;
use Drupal\styleguide\Plugin\StyleguidePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders all found layouts from core Layout Discovery module.
 *
 * @Plugin(
 *   id = "layout_styleguide",
 *   label = @Translation("Layouts Styleguide elements")
 * )
 */
class LayoutStyleguide extends StyleguidePluginBase {

  /**
   * The styleguide generator service.
   *
   * @var \Drupal\styleguide\Generator
   */
  protected $generator;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * Constructs a new LayoutStyleguide object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\styleguide\GeneratorInterface $styleguide_generator
   *   The styleguide generator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface|null $layout_plugin_manager
   *   The layout plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GeneratorInterface $styleguide_generator, ModuleHandlerInterface $module_handler, LayoutPluginManagerInterface $layout_plugin_manager = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->generator = $styleguide_generator;
    $this->moduleHandler = $module_handler;
    $this->layoutPluginManager = $layout_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('styleguide.generator'),
      $container->get('module_handler'),
      $container->has('plugin.manager.core.layout') ? $container->get('plugin.manager.core.layout') : NULL
    );
  }

  /**
   * {@inheritdoc}
   */
  public function items() {
    if (!$this->layoutPluginManager) {
      return [];
    }
    $items = [];

    foreach ($this->layoutPluginManager->getGroupedDefinitions() as $group => $layouts) {

      $items[$group] = [
        'title' => $group,
        'content' => [],
        'group' => $this->t('Layouts'),
      ];

      /** @var \Drupal\Core\Layout\LayoutDefinition $definition */
      foreach ($layouts as $layout => $definition) {

        $details = [];

        if ($property = $definition->id()) {
          $details[] = $this->t('ID: %property', [
            '%property' => $property,
          ]);
        }
        if ($property = $definition->getDescription()) {
          $details[] = $this->t('Description: %property', [
            '%property' => $property,
          ]);
        }
        if ($property = $definition->getProvider()) {
          $details[] = $this->t('Provider: %property', [
            '%property' => $property,
          ]);
        }
        if ($property = $definition->getDefaultRegion()) {
          $details[] = $this->t('Default region: %property', [
            '%property' => $property,
          ]);
        }
        if ($property = $definition->getLibrary()) {
          $details[] = $this->t('Library: %property', [
            '%property' => $property,
          ]);
        }

        $build = [];
        if ($region_labels = $definition->getRegionLabels()) {
          $regions = [];
          foreach ($region_labels as $id => $label) {
            $regions[$id] = [
              '#type' => 'inline_template',
              '#template' => '<span class="block-region demo-block">{{ label }} ({{ id }})</span>',
              '#context' => [
                'id' => $id,
                'label' => $label,
              ],
            ];
          }
          $layoutInstance = $this->layoutPluginManager->createInstance($definition->id());
          $build = $layoutInstance->build($regions);
        }

        $items[$group]['content'][$layout] = [
          '#type' => 'details',
          '#title' => $definition->getLabel(),
          '#open' => FALSE,
          '#description' => [
            'info' => [
              '#type' => 'container',
              '#attributes' => [
                'class' => 'styleguide__layout-info',
              ],
              'icon' => $definition->getIcon(60, 80, 1, 3),
              'details' => [
                '#theme' => 'item_list',
                '#items' => $details,
              ],
            ],
            'body' => [
              'build' => $build,
            ],
          ],
        ];
      }
    }
    return $items;
  }

}
