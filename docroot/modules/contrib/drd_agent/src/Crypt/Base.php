<?php

namespace Drupal\drd_agent\Crypt;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base encryption functionality.
 *
 * @ingroup drd
 */
class Base implements BaseInterface {

  /**
   * {@inheritdoc}
   */
  public static function getInstance(ContainerInterface $container, $method, array $settings): BaseMethodInterface {
    $classname = "\\Drupal\\drd_agent\\Crypt\\Method\\$method";
    return new $classname($container, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function getMethods(ContainerInterface $container, $instances = FALSE): array {
    $methods = [];
    foreach (['MCrypt', 'OpenSSL', 'TLS'] as $item) {
      $classname = "\\Drupal\\drd_agent\\Crypt\\Method\\$item";
      /* @var BaseMethodInterface $method */
      $method = new $classname($container);
      if ($method instanceof BaseMethodInterface && $method->isAvailable()) {
        if ($instances) {
          $methods[$method->getLabel()] = $method;
        }
        else {
          $methods[$method->getLabel()] = [
            'classname' => $classname,
            'cipher' => $method->getCipherMethods(),
          ];
        }
      }
    }
    return $methods;
  }

}
