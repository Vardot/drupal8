<?php

namespace Drupal\page_manager\Event;

use Drupal\page_manager\PageInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Wraps a page entity for event subscribers.
 *
 * @see \Drupal\page_manager\Event\PageManagerEvents::PAGE_CONTEXT
 */
class PageManagerContextEvent extends Event {

  /**
   * The page entity the context is gathered for.
   *
   * @var \Drupal\page_manager\PageInterface
   */
  protected $page;

  /**
   * The request for this event.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Creates a new PageManagerContextEvent.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The request for this event.
   */
  public function __construct(PageInterface $page, Request $request = NULL) {
    $this->page = $page;
    $this->request = $request;
  }

  /**
   * Returns the page entity for this event.
   *
   * @return \Drupal\page_manager\PageInterface
   *   The page entity.
   */
  public function getPage() {
    return $this->page;
  }

  /**
   * Returns the request for this event.
   *
   * @return null|\Symfony\Component\HttpFoundation\Request
   *   The request for this event
   */
  public function getRequest() {
    return $this->request;
  }

}
