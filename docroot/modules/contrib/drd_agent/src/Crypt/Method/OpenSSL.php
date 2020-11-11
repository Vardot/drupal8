<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Drupal\drd_agent\Crypt\Method;


use Drupal\drd_agent\Crypt\BaseMethod;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides OpenSSL encryption functionality.
 *
 * @ingroup drd
 */
class OpenSSL extends BaseMethod {

  private $cipher;

  private $iv;

  private $password;

  private $supportedCipher = [
    'aes-256-ctr' => 32,
    'aes-128-cbc' => 16,
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container, array $settings = []) {
    parent::__construct($container);
    $this->cipher = $settings['cipher'] ?? '';
    $this->password = $settings['password'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'OpenSSL';
  }

  /**
   * {@inheritdoc}
   */
  public function getCipher(): string {
    return $this->cipher;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword(): string {
    return base64_decode($this->password);
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    return function_exists('openssl_encrypt');
  }

  /**
   * {@inheritdoc}
   */
  public function getCipherMethods(): array {
    $result = [];
    $available = openssl_get_cipher_methods();
    foreach ($this->supportedCipher as $cipher => $keyLength) {
      if (in_array($cipher, $available, TRUE)) {
        $result[$cipher] = $cipher;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getIv(): string {
    if (empty($this->iv)) {
      $nonceSize = openssl_cipher_iv_length($this->cipher);
      $strong = TRUE;
      /** @noinspection CryptographicallySecureRandomnessInspection */
      $this->iv = openssl_random_pseudo_bytes($nonceSize, $strong);
      if ($strong === FALSE || $this->iv === FALSE) {
        $this->logger->warning('Your systm does not produce secure randomness.');
      }
    }
    return $this->iv;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt(array $args): string {
    return empty($this->password) ?
      '' :
      openssl_encrypt(
        serialize($args),
        $this->cipher,
        $this->getPassword(),
        OPENSSL_RAW_DATA,
        $this->getIv()
      );
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($body, $iv) {
    $this->iv = $iv;
    /** @noinspection UnserializeExploitsInspection */
    return unserialize(openssl_decrypt(
      $body,
      $this->cipher,
      $this->getPassword(),
      OPENSSL_RAW_DATA,
      $this->iv
    ));
  }

}

if (!defined('OPENSSL_RAW_DATA')) {
  define('OPENSSL_RAW_DATA', 1);
}
