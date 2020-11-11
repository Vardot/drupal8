<?php

namespace Drupal\varbase_api;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * VarbaseApiServiceProvider Class Doc Comment.
 *
 * @category Class
 * @package Varbase
 */
class VarbaseApiServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    parent::alter($container);

    if ($container->hasDefinition('metatag.normalizer.metatag_field')) {
      $container
        ->getDefinition('metatag.normalizer.metatag_field')
        ->clearTag('normalizer');
    }
    if ($container->hasDefinition('metatag.normalizer.metatag')) {
      $container
        ->getDefinition('metatag.normalizer.metatag')
        ->clearTag('normalizer');
    }
    if ($container->hasDefinition('metatag.normalizer.metatag.hal')) {
      $container
        ->getDefinition('metatag.normalizer.metatag.hal')
        ->clearTag('normalizer');
    }
  }

}
