<?php

namespace Drupal\styleguide\Theme;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Styleguide Theme Negotiator.
 */
class StyleguideThemeNegotiator implements ThemeNegotiatorInterface, ContainerInjectionInterface {

  /**
   * Theme machine name.
   *
   * @var string
   */
  public $themeName;

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * StyleguideThemeNegotiator constructor.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler service.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    new static(
      $container->get('theme_handler')
    );
  }

  /**
   * Whether this theme negotiator should be used to set the theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return bool
   *   TRUE if this negotiator should be used or FALSE to let other negotiators
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match) {
    if (strpos($route_match->getRouteName(), 'styleguide.') === FALSE) {
      return FALSE;
    }

    $themes = $this->themeHandler->rebuildThemeData();
    foreach ($themes as &$theme) {
      if (!empty($theme->info['hidden'])) {
        continue;
      }
      if ($theme->status) {
        $route_name = $route_match->getRouteName();
        if ($route_name == 'styleguide.' . $theme->getName() || $route_name == 'styleguide.maintenance_page.' . $theme->getName()) {
          $this->themeName = $theme->getName();
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Determine the active theme for the request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return string|null
   *   Returns the active theme name, else return NULL.
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->themeName;
  }

}
