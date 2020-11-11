<?php

namespace Drupal\varbase_api\Exception;

/**
 * KeyGenerationException Class Doc Comment.
 *
 * @category Class
 * @package Varbase
 */
class KeyGenerationException extends \RuntimeException {

  /**
   * KeyGenerationException constructor.
   *
   * @param string $message
   *   Exception message.
   * @param int $code
   *   Exception code.
   * @param \Exception $previous
   *   Previous exception.
   */
  public function __construct($message = "", $code = 0, \Exception $previous = NULL) {
    if (empty($message)) {
      $message = openssl_error_string() ?: 'An internal error occurred';
    }
    parent::__construct($message, $code, $previous);
  }

}
