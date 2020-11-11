<?php

namespace Drupal\betterlogin\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Better Login Subscriber class.
 */
class BetterLoginSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs the BetterLoginSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Function checkForRedirection.
   *
   * Redirection for anonymous users.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   GetResponseEvent event.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    if ($this->currentUser->isAnonymous()) {
      // Anonymous user.
      if ($event->getRequest()->query->get('user')) {
        $loginUrl = Url::fromRoute('user.login', ['destination' => 'user'])->toString();
        $event->setResponse(new RedirectResponse($loginUrl));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    return $events;
  }

}
