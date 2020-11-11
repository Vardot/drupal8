<?php

namespace Drupal\drd_agent\Crypt\Method;


use Drupal\drd_agent\Crypt\BaseMethod;

/**
 * Provides security over TLS without additional encryption.
 *
 * @ingroup drd
 */
class TLS extends BaseMethod {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'TLS';
  }

  /**
   * {@inheritdoc}
   */
  public function getCipher(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword(): string {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    // TODO: properly find out if the remote site is running on TLD.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCipherMethods(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getIv(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt(array $args): string {
    return serialize($args);
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($body, $iv) {
    /** @noinspection UnserializeExploitsInspection */
    return unserialize($body);
  }

}
