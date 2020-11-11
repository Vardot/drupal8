<?php

namespace Drupal\drd_agent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\drd_agent\Setup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Authorize a new dashboard for this drd-agent.
 */
class Authorize extends FormBase {

  /**
   * @var \Drupal\drd_agent\Setup
   */
  protected $setupService;

  /**
   * Authorize constructor.
   *
   * @param \Drupal\drd_agent\Setup $setup_service
   */
  public function __construct(Setup $setup_service) {
    $this->setupService = $setup_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('drd_agent.setup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drd_agent_authorize_form';
  }

  /**
   * Build the authorization form to paste the token from DRD.
   *
   * @param array $form
   *   The form array.
   *
   * @return array
   *   The form.
   */
  protected function buildFormToken(array $form): array {
    $form['token'] = [
      '#type' => 'textarea',
      '#title' => t('Authentication token'),
      '#description' => t('Paste the token for this domain from the DRD dashboard, which you want to authorize.'),
      '#default_value' => '',
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Validate'),
    ];

    return $form;
  }

  /**
   * Build the authorization confirmation form.
   *
   * @param array $form
   *   The form array.
   *
   * @return array
   *   The form.
   */
  protected function buildFormConfirmation(array $form): array {
    $form['attention'] = [
      '#markup' => t('You are about to grant admin access to the Drupal Remote Dashboard on the following domain:'),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
    $form['domain'] = [
      '#markup' => $this->setupService->getDomain(),
      '#prefix' => '<div class="domain">',
      '#suffix' => '</div>',
    ];
    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => t('Cancel'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Grant admin access'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = empty($_SESSION['drd_agent_authorization_values']) ?
      $this->buildFormToken($form) :
      $this->buildFormConfirmation($form);

    $form['#attributes'] = [
      'class' => ['drd-agent-auth'],
    ];
    $form['#attached']['library'][] = 'drd_agent/general';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($_SESSION['drd_agent_authorization_values'])) {
      $_SESSION['drd_agent_authorization_values'] = $form_state->getValue('token');
    }
    else {
      if ($form_state->getValue('op') === $form['submit']['#value']) {
        $values = $this->setupService->execute();
        $form_state->setResponse(TrustedRedirectResponse::create($values['redirect']));
      }
      unset($_SESSION['drd_agent_authorization_values']);
    }
  }

}
