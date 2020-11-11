<?php
/**
 * @file
 * Contains Drupal\page_manager\PageManagerHelper.
 */

namespace Drupal\page_manager;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Utility\Token;
use Drupal\Core\Render\Markup;
use Drupal\Component\Render\HtmlEscapedText;

/**
 * Helper service for page_manager.
 */
class PageManagerHelper {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /** The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new PageManagerHelper.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(Token $token, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->token = $token;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Title callback for page variants.
   *
   * @param mixed $page_manager_page_variant
   *   The PageVariant entity.
   *
   * @return string
   *   The title of the page variant after replacing any tokens.
   *
   * @see \Drupal\page_manager\Routing\PageManagerRoutes::alterRoutes()
   */
  public function getVariantTitle($page_manager_page_variant = NULL) {
    $variant_title = '';
    if ($page_manager_page_variant != NULL) {
      if (is_string($page_manager_page_variant)) {
        $page_manager_page_variant = $this->entityRepository->loadEntityByConfigTarget('page_variant', $page_manager_page_variant);
      }
      $variant_title = $this->getTitle($page_manager_page_variant->getTitle(), $page_manager_page_variant->getContexts());
    }
    return $variant_title;
  }

  /**
   * Gets the title using given context and replaces tokens.
   *
   * @param string $title
   *   The title string.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   An array of set context values, keyed by context name.
   *
   * @return string
   *   The title after replacing any tokens for given context.
   */
  public function getTitle($title, array $contexts) {
    $contexts = $this->fixContexts($contexts);
    // Token replace only escapes replacement values, ensure a consistent
    // behavior by also escaping the input and then returning it as a Markup
    // object to avoid double escaping.
    // @todo: Simplify this when core provides an API for this in
    //   https://www.drupal.org/node/2580723.
    $title = (string) $this->token->replace($title, $this->getContextAsTokenData($contexts));
    return Markup::create($title);
  }

  /**
   * Sometimes the contexts doesn't have the entity loaded. Fix them.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   An array of set context values, keyed by context name.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   Fixed contexts.
   */
  protected function fixContexts(array $contexts) {
    $fixed_contexts = [];
    foreach ($contexts as $context_name => $context) {
      $data_type = $context->getContextDefinition()->getDataType();
      if (preg_match('/entity:(\w+)/', $data_type, $matches)) {
        $entity_type = $matches[1];
        if (is_numeric($context->getContextValue())) {
          $storage = $this->entityTypeManager->getStorage($entity_type);
          $entity = $storage->load($context->getContextValue());
          if ($entity) {
            $context = new Context($context->getContextDefinition(), $entity);
          }
        }
      }
      $fixed_contexts[$context_name] = $context;
    }
    return $fixed_contexts;
  }

  /**
   * Returns available context as token data.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   An array of set context values, keyed by context name.
   *
   * @return array
   *   An array with token data values keyed by token type.
   */
  protected function getContextAsTokenData(array $contexts) {
    $data = [];
    foreach ($contexts as $context) {
      // @todo Simplify this when token and typed data types are unified in
      //   https://drupal.org/node/2163027.
      if (strpos($context->getContextDefinition()->getDataType(), 'entity:') === 0) {
        $token_type = substr($context->getContextDefinition()->getDataType(), 7);
        if ($token_type == 'taxonomy_term') {
          $token_type = 'term';
        }
        $data[$token_type] = $context->getContextValue();
      }
    }
    return $data;
  }

}
