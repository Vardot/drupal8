<?php

namespace Drupal\drd_agent\Crypt;


use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base encryption method.
 *
 * @ingroup drd
 */
abstract class BaseMethod implements BaseMethodInterface {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * BaseMethod constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
    /** @noinspection NullPointerExceptionInspection */
    $this->logger = $container->get('logger.factory')->get('DRD Agent');
  }

  /**
   * Callback to encrypt and decrypt files.
   *
   * @param string $mode
   *   This is "-e" to encrypt or "-d" to decrypt.
   * @param string $in
   *   Input filename.
   * @param string $out
   *   Output filename.
   *
   * @return int
   *   Exit code of the openssl command.
   */
  private function cryptFileExecute($mode, $in, $out): int {
    $output = [];
    $cmd = [
      'openssl',
      $this->getCipher(),
      $mode,
      '-a',
      '-salt',
      '-in',
      $in,
      '-out',
      $out,
      '-k',
      base64_encode($this->getPassword()),
    ];
    exec(implode(' ', $cmd), $output, $ret);
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function encryptFile($filename): string {
    if ($this->getCipher()) {
      exec('openssl version', $output, $ret);
      if ($ret === 0) {
        $in = $filename;
        $filename .= '.openssl';
        if ($this->cryptFileExecute('-e', $in, $filename) !== 0) {
          $filename = $in;
        }
      }
    }
    return $filename;
  }

}
