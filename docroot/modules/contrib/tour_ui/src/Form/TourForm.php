<?php

namespace Drupal\tour_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\tour\TipPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Form controller for the tour entity edit forms.
 */
class TourForm extends EntityForm {

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Tip Plugin Manager service.
   *
   * @var \Drupal\tour\TipPluginManager
   */
  protected $tipPluginManager;

  /**
   * The Language Manager Service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('plugin.manager.tour.tip'),
      $container->get('messenger')
    );
  }

  /**
   * Constructs a TourForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The Language Manager service.
   * @param \Drupal\tour\TipPluginManager $tipPluginManager
   *   The Tip Plugin Manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger Service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $languageManager, TipPluginManager $tipPluginManager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $languageManager;
    $this->tipPluginManager = $tipPluginManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $tour = $this->entity;
    $form = parent::form($form, $form_state);
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tour name'),
      '#required' => TRUE,
      '#default_value' => $tour->label(),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => '\Drupal\tour\Entity\Tour::load',
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ],
      '#default_value' => $tour->id(),
      '#disabled' => !$tour->isNew(),
    ];

    $form['langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      // Default to the content language opposed to und (no language).
      '#default_value' => empty($tour->language()) ? $this->languageManager->getCurrentLanguage()->getId() : $tour->language()->getId(),
    ];
    $form['module'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Module name'),
      '#description' => $this->t('Each tour needs a module.'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'tour_ui.get_modules',
      '#default_value' => $tour->get('module'),
    ];

    $default_routes = [];
    if ($routes = $tour->getRoutes()) {
      foreach ($routes as $route) {
        $default_routes[] = $route['route_name'];
        if (isset($route['route_params'])) {
          foreach ($route['route_params'] as $key => $value) {
            $default_routes[] = '- ' . $key . ':' . $value;
          }
        }
      }
    }
    $form['routes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Routes'),
      '#default_value' => implode("\n", $default_routes),
      '#rows' => 5,
      '#description' => $this->t('Provide a list of routes that this tour will be displayed on. Add route_name first then optionally route parameters. For example <pre>entity.node.canonical<br/>- node:2</pre> will only show on the <em>node/2</em> page.<br/>NOTE: route parameters are <strong>not validated yet</strong>.'),
    ];

    $form['find-routes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Find route or path fragment'),
      '#description' => $this->t('You can type a route name or path fragment.'),
      '#required' => FALSE,
      '#autocomplete_route_name' => 'tour_ui.get_routes',
    ];

    // Don't show the tips on the inital add.
    if ($tour->isNew()) {
      return $form;
    }

    // Start building the list of tips assigned to this tour.
    $form['tips'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#caption' => [['#markup' => $this->t('Tips provided by this tour. By clicking on Operations buttons, every changes which are not saved will be lost.')]],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'tip-order-weight',
        ],
      ],
      '#weight' => 40,
    ];

    // Populate the table with the assigned tips.
    $tips = $tour->getTips();
    if (!empty($tips)) {
      foreach ($tips as $tip) {
        $tip_id = $tip->get('id');
        try {
          $form['#data'][$tip_id] = $tip->getConfiguration();
        }
        catch (\Error $e) {
          $this->messenger->addMessage($this->t('Tip %tip is not configurable. You cannot save this tour.', ['%tip' => $tip->getLabel()]), 'warning');
        }
        $form['tips'][$tip_id]['#attributes']['class'][] = 'draggable';
        $form['tips'][$tip_id]['label'] = [
          '#plain_text' => $tip->get('label'),
        ];

        $form['tips'][$tip_id]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $tip->get('label')]),
          '#delta' => 100,
          '#title_display' => 'invisible',
          '#default_value' => $tip->get('weight'),
          '#attributes' => [
            'class' => ['tip-order-weight'],
          ],
        ];

        // Provide operations links for the tip.
        $links = [];
        if (method_exists($tip, 'buildConfigurationForm')) {
          $links['edit'] = [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('tour_ui.tip.edit', ['tour' => $tour->id(), 'tip' => $tip_id]),
          ];
        }
        $links['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('tour_ui.tip.delete', ['tour' => $tour->id(), 'tip' => $tip_id]),
        ];
        $form['tips'][$tip_id]['operations'] = [
          '#type' => 'operations',
          '#links' => $links,
        ];
      }
    }

    // Build the new tour tip addition form and add it to the tips list.
    $tip_definitions = $this->tipPluginManager->getDefinitions();
    $tip_definition_options = [];
    foreach ($tip_definitions as $tip => $definition) {
      if (method_exists($definition['class'], 'buildConfigurationForm')) {
        $tip_definition_options[$tip] = $definition['title'];
      }
    }

    $user_input = $form_state->getUserInput();
    $form['tips']['new'] = [
      '#tree' => FALSE,
      '#weight' => isset($user_input['weight']) ? $user_input['weight'] : 0,
      '#attributes' => [
        'class' => ['draggable'],
      ],
    ];
    $form['tips']['new']['new'] = [
      '#type' => 'select',
      '#title' => $this->t('Tip'),
      '#title_display' => 'invisible',
      '#options' => $tip_definition_options,
      '#empty_option' => $this->t('Select a new tip'),
    ];
    $form['tips']['new']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for new tip'),
      '#title_display' => 'invisible',
      '#default_value' => count($form['tips']) - 1,
      '#attributes' => [
        'class' => ['tip-order-weight'],
      ],
    ];
    $form['tips']['new']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#validate' => [[$this, 'tipValidate']],
      '#submit' => [[$this, 'tipAdd']],
    ];

    return $form;
  }

  /**
   * Validate handler.
   */
  public function tipValidate($form, FormStateInterface $form_state) {
    if (!$form_state->getValue('new')) {
      $form_state->setError($form['tips']['new']['new'], $this->t('Select a new tip.'));
    }
  }

  /**
   * Submit handler.
   */
  public function tipAdd($form, FormStateInterface $form_state) {
    $tour = $this->getEntity($form_state);

    $this::submitForm($form, $form_state, FALSE);

    $weight = 0;
    if (!$form_state->isValueEmpty('tips')) {
      // Get last weight.
      foreach ($form_state->getValue('tips') as $tip) {
        if ($tip['weight'] > $weight) {
          $weight = $tip['weight'] + 1;
        }
      }
    }

    $stub = $this->tipPluginManager->createInstance($form_state->getValue('new'), []);

    // If a form is available for this tip then redirect to a add page.
    $stub_form = $stub->buildConfigurationForm([], new FormState());
    if (isset($stub_form)) {
      // Redirect to the appropriate page to add this new tip.
      $form_state->setRedirect('tour_ui.tip.add', ['tour' => $tour->id(), 'type' => $form_state->getValue('new')], ['query' => ['weight' => $weight]]);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, $redirect = TRUE) {
    // TODO: validate the routes
    $routes = $this->routesFromArray($form_state->getValue('routes'));

    // Form cannot be validated if a tip has no #data, so no way to export
    // configuration.
    if (!$form_state->isValueEmpty('tips')) {
      foreach ($form_state->getValue('tips') as $key => $values) {
        if (!isset($form['#data'][$key])) {
          $form_state->setError($form['tips'][$key], $this->t('You cannot save the tour while %tip tip cannot be exported.', ['%tip' => $this->getEntity()->getTip($key)->getLabel()]));
        }
      }
    }
  }

  /**
   * Rebuild the lines into route structures.
   *
   * - route_name
   * - route_params
   *   - key:value
   *
   * @param string $routes_in
   *
   * @return array
   */
  protected function routesFromArray($routes_in) {
    // Normalize the new lines
    $routes_in = preg_replace("/(\r\n?|\n)/", "\n", $routes_in);
    $routes_in = explode("\n", $routes_in);
    // trim each line
    $routes_in = array_map('trim', $routes_in);

    $routes = [];
    $route = null;
    foreach($routes_in as $line) {
      if (empty($line)) {
        continue;
      }
      if (strpos($line, '-')!== 0) {
        $routes[] = [];
        $route = &$routes[count($routes)-1];
        $route['route_name'] = $line;
      } else {
        if (count($routes) === 0) {
          // abort when having a route_params without a route_name
          break;
        }
        list($key, $value) = explode(':', $line, 2);
        $key = trim(substr($key, 1));
        $value = trim($value);
        $route['route_params'][$key] = $value;
      }
    }
    return $routes;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $redirect = TRUE) {
    // Filter out invalid characters and convert to an array.
    $routes = $this->routesFromArray($form_state->getValue('routes'));

    $form_state->setValue('routes', array_filter($routes));

    // Merge the form values in with the current configuration.
    if (!$form_state->isValueEmpty('tips')) {
      $tips = [];
      foreach ($form_state->getValue('tips') as $key => $values) {
        $data = $form['#data'][$key];
        $tips[$key] = array_merge($data, $values);
        if (!is_array($tips[$key]['attributes'])) {
          $tips[$key]['attributes'] = [];
        }
      }
      $form_state->setValue('tips', $tips);
    }
    else {
      $form_state->setValue('tips', []);
    }

    parent::submitForm($form, $form_state);

    // Redirect to Entity edition.
    if ($redirect) {
      $form_state->setRedirect('entity.tour.edit_form', ['tour' => $this->entity->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity($form_state);
    $form_state->setRedirect('entity.tour.delete_form', ['tour' => $entity->id()]);
  }

}
