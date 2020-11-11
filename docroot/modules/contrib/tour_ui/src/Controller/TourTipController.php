<?php

namespace Drupal\tour_ui\Controller;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormState;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\tour\Entity\Tour;
use Drupal\tour\TipPluginManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles page returns for tour tip.
 */
class TourTipController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The Tip plugin manager.
   *
   * @var \Drupal\tour\TipPluginManager
   */
  protected $tipPluginManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The Request Stack Service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new TourTipController object.
   *
   * @param \Drupal\tour\TipPluginManager $tipPluginManager
   *   The Tip Plugin Manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(TipPluginManager $tipPluginManager, FormBuilderInterface $form_builder, RequestStack $requestStack) {
    $this->tipPluginManager = $tipPluginManager;
    $this->formBuilder = $form_builder;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.tour.tip'),
      $container->get('form_builder'),
      $container->get('request_stack')
    );
  }

  /**
   * Provides a creation form for a new tip to be added to a tour entity.
   *
   * @param \Drupal\tour\Entity\Tour $tour
   *   The tour in which the tip needs to be added to.
   * @param string $type
   *   The type of tip that will be added to the tour.
   *
   * @return array
   *   A renderable form array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function add(Tour $tour, $type = '') {
    // We need a type to build this form.
    if (!$type) {
      throw new NotFoundHttpException();
    }

    // Default values.
    $defaults = [
      'plugin' => Html::escape($type),
      'weight' => $this->requestStack->getCurrentRequest()->query->get('weight'),
    ];

    // Build a new stub tip.
    $stub = $this->tipPluginManager->createInstance($type, $defaults);

    // Attach the tour, tip and if it's new to the form.
    $form_state = new FormState();
    $form_state->setFormState([
      '#tour' => $tour,
      '#tip' => $stub,
      '#new' => TRUE,
    ]);
    return $this->formBuilder->buildForm('\Drupal\tour_ui\Form\TourTipForm', $form_state);
  }

  /**
   * Provides an edit form for tip to be updated against a tour entity.
   *
   * @param \Drupal\tour\Entity\Tour $tour
   *   The tour in which the tip is being edited against.
   * @param string $tip
   *   The identifier of tip that will be edited against the tour.
   *
   * @return array
   *   A renderable form array.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function edit(Tour $tour, $tip = '') {
    // We need a tip to build this form.
    if (!$tip && !$tour) {
      throw new NotFoundHttpException();
    }

    try {
      $the_tip = $tour->getTip($tip);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException();
    }

    // Attach the tour and tip.
    $form_state = new FormState();
    $form_state->setFormState([
      '#tour' => $tour,
      '#tip' => $the_tip,
    ]);
    return $this->formBuilder->buildForm('\Drupal\tour_ui\Form\TourTipForm', $form_state);
  }

}
