<?php

namespace Drupal\drd_agent\Commands;

use Drupal\drd_agent\Setup;
use Drush\Commands\DrushCommands;

/**
 * Class Base.
 *
 * @package Drupal\drd_agent
 */
class Drush extends DrushCommands {

  /**
   * @var \Drupal\drd_agent\Setup
   */
  protected $setupService;

  /**
   * Drush constructor.
   *
   * @param \Drupal\drd_agent\Setup $setup_service
   */
  public function __construct(Setup $setup_service) {
    parent::__construct();
    $this->setupService = $setup_service;
  }

  /**
   * Configure this domain for communication with a DRD instance.
   *
   * @param string $token
   *   Base64 and json encoded array of all variables required such that
   *   DRD can communicate with this domain in the future.
   *
   * @command drd:agent:setup
   * @aliases drd-agent-setup
   */
  public function setup($token) {
    $_SESSION['drd_agent_authorization_values'] = $token;
    $this->setupService->execute();
    unset($_SESSION['drd_agent_authorization_values']);
  }

}
