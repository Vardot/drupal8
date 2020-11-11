<?php

namespace Drupal\drd_agent\Agent\Auth;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Remote DRD Auth Methods.
 */
abstract class Base implements BaseInterface {

  /**
   * All the settings of the implementing authentication method.
   *
   * @var array
   */
  protected $storedSettings;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * Base constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_User
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\user\UserAuthInterface $user_auth
   */
  public function __construct(AccountInterface $current_User, ConfigFactoryInterface $config_factory, UserAuthInterface $user_auth) {
    $this->currentUser = $current_User;
    $this->configFactory = $config_factory;
    $this->userAuth = $user_auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('user.auth')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getMethods(ContainerInterface $container): array {
    $methods = array(
      'username_password' => 'UsernamePassword',
      'shared_secret' => 'SharedSecret',
    );
    foreach ($methods as $key => $class) {
      $classname = "\\Drupal\\drd_agent\\Agent\\Auth\\$class";
      /** @noinspection PhpUndefinedMethodInspection */
      $methods[$key] = $classname::create($container);
    }
    return $methods;
  }

  /**
   * {@inheritdoc}
   */
  final public function validateUuid($uuid): bool {
    $config = $this->configFactory->get('drd_agent.settings');
    $authorised = $config->get('authorised') ?? [];
    if (empty($authorised[$uuid])) {
      return FALSE;
    }
    $this->storedSettings = (array) $authorised[$uuid]['authsetting'];
    return TRUE;
  }

}
