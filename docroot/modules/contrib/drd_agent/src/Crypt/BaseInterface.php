<?php

namespace Drupal\drd_agent\Crypt;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an interface for encryption.
 *
 * @ingroup drd
 */
interface BaseInterface {

  /**
   * Create instance of a crypt object of given method with provided settings.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param string $method
   *   ID of the crypt method.
   * @param array $settings
   *   Settings of the crypt instance.
   *
   * @return BaseMethodInterface
   *   The crypt object.
   */
  public static function getInstance(ContainerInterface $container, $method, array $settings): BaseMethodInterface;

  /**
   * Get a list of crypt methods, either just their ids or instances of each.
   *
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param bool $instances
   *   Whether to receive ids (FALSE) or instances (TRUE).
   *
   * @return array
   *   List of crypt methods.
   */
  public static function getMethods(ContainerInterface $container, $instances = FALSE): array ;

}
