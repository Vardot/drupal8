<?php

namespace Drupal\drd_agent\Agent\Auth;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface for authentication methods.
 */
interface BaseInterface {

  /**
   * Get a list of all implemented authentication methods.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return array
   *   Array of all implemented authentication methods.
   */
  public static function getMethods(ContainerInterface $container): array;

  /**
   * Verify if the given UUID is authorised to access this site.
   *
   * @param string $uuid
   *   UUID of the authentication object that should be validated.
   *
   * @return bool
   *   TRUE if authenticated, FALSE otherwise.
   */
  public function validateUuid($uuid): bool;

  /**
   * Validate authentication of the current request with the given settings.
   *
   * @param array $settings
   *   Authentication settings from the request.
   *
   * @return bool
   *   TRUE if authenticated, FALSE otherwise.
   */
  public function validate(array $settings): bool;

}
