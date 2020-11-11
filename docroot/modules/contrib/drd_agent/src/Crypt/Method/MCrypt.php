<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Drupal\drd_agent\Crypt\Method;


use Drupal\drd_agent\Crypt\BaseMethod;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides MCrypt encryption functionality.
 *
 * @ingroup drd
 */
class MCrypt extends BaseMethod {

  private $cipher;

  private $mode;

  private $iv;

  private $password;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container, array $settings = []) {
    parent::__construct($container);
    $this->cipher = $settings['cipher'] ?? 'rijndael-256';
    $this->mode = $settings['mode'] ?? 'cbc';
    $this->password = $settings['password'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'MCrypt';
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
    return function_exists('mcrypt_encrypt');
  }

  /**
   * {@inheritdoc}
   */
  public function getCipherMethods(): array {
    return [
      'rijndael-128',
      'rijndael-192',
      'rijndael-256',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIv(): string {
    if (empty($this->iv)) {
      $nonceSize = mcrypt_get_iv_size($this->cipher, $this->mode);
      /** @noinspection CryptographicallySecureRandomnessInspection */
      $this->iv = mcrypt_create_iv($nonceSize, MCRYPT_DEV_URANDOM);
    }
    return $this->iv;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt(array $args): string {
    return mcrypt_encrypt(
      $this->cipher,
      $this->getPassword(),
      serialize($args),
      $this->mode,
      $this->getIv()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($body, $iv) {
    $this->iv = $iv;
    /** @noinspection UnserializeExploitsInspection */
    return unserialize(mcrypt_decrypt(
      $this->cipher,
      $this->getPassword(),
      $body,
      $this->mode,
      $this->iv
    ));
  }

}

if (!defined('MCRYPT_DEV_URANDOM')) {
  define('MCRYPT_DEV_URANDOM', 1);
}
