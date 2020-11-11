<?php

namespace Drupal\menu_position;

use Symfony\Component\DependencyInjection\Reference;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Menu Position service provider.
 */
class MenuPositionServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Override the menu active trail with a new class.
    $definition = $container->getDefinition('menu.active_trail');
    $definition->setClass('Drupal\menu_position\Menu\MenuPositionActiveTrail');
    $definition->addArgument(new Reference('entity_type.manager'));
    $definition->addArgument(new Reference('config.factory'));
  }

}
