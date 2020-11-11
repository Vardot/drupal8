<?php

namespace Drupal\menu_position\Plugin\Menu;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines menu links provided by menu position rules.
 *
 * @see \Drupal\menu_position\Plugin\Derivative\MenuPositionLink
 */
class MenuPositionLink extends MenuLinkBase implements ContainerFactoryPluginInterface {

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The title resolver service.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Menu position settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration,
  $plugin_id,
  $plugin_definition,
  EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory,
  RouteMatchInterface $route_match,
  RequestStack $request_stack,
                              AdminContext $admin_context,
  TitleResolverInterface $title_resolver,
  RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->settings = $config_factory->get('menu_position.settings');

    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->adminContext = $admin_context;
    $this->titleResolver = $title_resolver;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('router.admin_context'),
      $container->get('title_resolver'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = [
    'parent' => 1,
    'weight' => 1,
  ];

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // When we're in an admin route we want to display the name of the menu
    // position rule.
    // @todo Ensure this translates properly when using configuration
    //   translation.
    if ($this->adminContext->isAdminRoute()) {
      return $this->pluginDefinition['title'];
    }
    // When we're on a non-admin route we want to display the page title.
    else {
      $title = $this->titleResolver->getTitle($this->requestStack->getCurrentRequest(), $this->routeMatch->getRouteObject());
      if (is_array($title)) {
        $title = $this->renderer->renderPlain($title);
      }
      return $title;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getPluginDefinition()['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    // Filter the list of updates to only those that are allowed.
    $overrides = array_intersect_key($new_definition_values, $this->overrideAllowed);
    // Update the definition.
    $this->pluginDefinition = $overrides + $this->getPluginDefinition();

    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) ($this->settings->get('link_display') === 'child');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
    // Noop.
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    $storage = $this->entityTypeManager->getStorage('menu_position_rule');
    $entity_id = $this->pluginDefinition['metadata']['entity_id'];
    $entity = $storage->load($entity_id);
    return $entity->toUrl();
  }

}
