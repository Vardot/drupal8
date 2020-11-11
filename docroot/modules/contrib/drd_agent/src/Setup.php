<?php

namespace Drupal\drd_agent;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Setup.
 *
 * @package Drupal\drd_agent
 */
class Setup {

  protected $values;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Setup constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Component\Datetime\TimeInterface $time
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(ConfigFactoryInterface $config_factory, TimeInterface $time, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->time = $time;
    $this->request = $request_stack->getCurrentRequest();
    $this->checkForRemoteSetupToken();
  }

  private function checkForRemoteSetupToken() {
    if (isset($_SESSION['drd_agent_authorization_values'])) {
      $this->setRemoteSetupToken($_SESSION['drd_agent_authorization_values']);
    }
  }

  /**
   * Set the remote setup token which contains the configuration.
   *
   * @param string $remoteSetupToken
   *   The remote setup token.
   *
   * @return $this
   */
  public function setRemoteSetupToken($remoteSetupToken): self {
    $values = strtr($remoteSetupToken, ['-' => '+', '_' => '/']);
    $this->values = json_decode(base64_decode($values), TRUE);
    return $this;
  }

  /**
   * Perform the configuration with the data from the token.
   *
   * @return array
   *   The configuration data for this domain.
   */
  public function execute(): array {
    $this->checkForRemoteSetupToken();
    $config = $this->configFactory->getEditable('drd_agent.settings');

    $authorised = $config->get('authorised') ?? [];

    $this->values['timestamp'] = $this->time->getRequestTime();
    $this->values['ip'] = $this->request->getClientIp();
    $authorised[$this->values['uuid']] = $this->values;

    $config->set('authorised', $authorised)->save(TRUE);
    return $this->values;
  }

  /**
   * Get the hostname to which we should redirect after confirmation.
   *
   * @return string
   *   The hostname.
   */
  public function getDomain(): string {
    $this->checkForRemoteSetupToken();
    return parse_url($this->values['redirect'], PHP_URL_HOST);
  }

}
