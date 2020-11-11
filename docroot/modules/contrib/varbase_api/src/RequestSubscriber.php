<?php

namespace Drupal\varbase_api;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * RequestSubscriber Class Doc Comment.
 *
 * @category Class
 * @package Varbase
 */
class RequestSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The OAuth key service.
   *
   * @var \Drupal\varbase_api\OAuthKey
   */
  protected $key;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Request Subscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\varbase_api\OAuthKey $key
   *   The OAuth keys service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   String Translation.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(RouteMatchInterface $route_match, OAuthKey $key, TranslationInterface $translation, MessengerInterface $messenger) {
    $this->routeMatch = $route_match;
    $this->key = $key;
    $this->stringTranslation = $translation;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'onRequest',
    ];
  }

  /**
   * On request function.
   */
  public function onRequest() {
    if ($this->routeMatch->getRouteName() == 'oauth2_token.settings' && $this->key->exists() == FALSE) {
      $url = Url::fromRoute('varbase_api.generate_keys');

      $this->messenger->addWarning(
        $this->t('You may wish to <a href=":generate_keys">generate a key pair</a> for OAuth authentication.', [
          ':generate_keys' => $url->toString(),
        ])
      );

    }
  }

}
