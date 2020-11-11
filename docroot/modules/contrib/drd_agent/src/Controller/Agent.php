<?php

namespace Drupal\drd_agent\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\drd_agent\Agent\Action\Base as ActionBase;
use Drupal\drd_agent\Crypt\Base as CryptBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Default.
 *
 * @package Drupal\drd_agent\Controller
 */
class Agent extends ControllerBase {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The agent configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Get an array of http response headers.
   *
   * @return array
   *   The array with headers.
   */
  public static function responseHeader(): array {
    return [
      'Content-Type' => 'text/plain; charset=utf-8',
      'X-DRD-Agent' => $_SERVER['HTTP_X_DRD_VERSION'],
    ];
  }

  /**
   * Agent constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ContainerInterface $container, ConfigFactoryInterface $configFactory) {
    $this->container = $container;
    $this->config = $configFactory->get('drd_agent.settings');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('config.factory')
    );
  }

  /**
   * Route callback to execute an action and return their result.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to DRD.
   * @throws \Exception
   */
  public function get(): Response {
    return $this->deliver(ActionBase::create($this->container)->run((bool) $this->config->get('debug_mode')));
  }

  /**
   * Route callback to retrieve a list of available crypt methods.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to DRD.
   * @throws \Exception
   */
  public function getCryptMethods(): Response {
    return $this->deliver(base64_encode(json_encode(CryptBase::getMethods($this->container))));
  }

  /**
   * Route callback to authorize a DRD instance by a secret.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to DRD.
   * @throws \Exception
   */
  public function authorizeBySecret(): Response {
    return $this->deliver(ActionBase::create($this->container)->authorizeBySecret((bool) $this->config->get('debug_mode')));
  }

  /**
   * Callback to deliver the result of the action in json format.
   *
   * @param string|Response $data
   *   The result which should be delivered back to DRD.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to DRD.
   */
  private function deliver($data): Response {
    return ($data instanceof Response) ? $data : new JsonResponse($data, 200, self::responseHeader());
  }

}
