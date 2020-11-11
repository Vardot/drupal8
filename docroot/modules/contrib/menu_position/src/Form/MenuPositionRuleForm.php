<?php

namespace Drupal\menu_position\Form;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuParentFormSelector;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Menu Position rule form.
 */
class MenuPositionRuleForm extends EntityForm {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  /**
   * The menu parent form selector.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelector
   */
  protected $menu_parent_form_selector;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menu_link_manager;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $condition_plugin_manager;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $context_repository;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $route_builder;

  /**
   * The constructor for the menu position rule form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Menu\MenuParentFormSelector $menu_parent_form_selector
   *   The menu parent form selector.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Condition\ConditionManager $condition_plugin_manager
   *   The condition plugin manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route building service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MenuParentFormSelector $menu_parent_form_selector,
    MenuLinkManagerInterface $menu_link_manager,
    ConditionManager $condition_plugin_manager,
    ContextRepositoryInterface $context_repository,
    RouteBuilderInterface $route_builder,
    MessengerInterface $messenger) {

    $this->entity_type_manager = $entity_type_manager;
    $this->menu_parent_form_selector = $menu_parent_form_selector;
    $this->menu_link_manager = $menu_link_manager;
    $this->condition_plugin_manager = $condition_plugin_manager;
    $this->context_repository = $context_repository;
    $this->route_builder = $route_builder;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('menu.parent_form_selector'),
      $container->get('plugin.manager.menu.link'),
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('router.builder'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Allow parent to construct base form, set tree value.
    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;

    // Set these for use when attaching condition forms.
    $form_state->setTemporaryValue('gathered_contexts', $this->context_repository->getAvailableContexts());

    // Get the menu position rule entity.
    /** @var \Drupal\menu_position\Entity\MenuPositionRule $rule */
    $rule = $this->entity;

    // Get the menu link for this rule.
    $menu_link = $rule->getMenuLinkPlugin();

    // Menu position label.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $rule->getLabel(),
      '#description' => $this->t("Label for the Menu Position rule."),
      '#required' => TRUE,
    ];

    // Menu position machine name.
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $rule->getId(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$rule->isNew(),
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('A flag for whether the menu position rule should be evaluated or is ignored.'),
      '#default_value' => $rule->isNew() ? TRUE : $rule->getEnabled(),
    ];

    // Menu position parent menu tree item.
    $options = $this->menu_parent_form_selector->getParentSelectOptions();
    $form['parent'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent menu item'),
      '#required' => TRUE,
      '#default_value' => (!$rule->isNew()) ? $menu_link->getMenuName() . ':' . $menu_link->getParent() : NULL,
      '#options' => $options,
      '#description' => $this->t('Select the place in the menu where the rule should position its menu links.'),
      '#attributes' => [
        'class' => ['menu-parent-select'],
      ],
    ];

    // Menu position conditions vertical tabs.
    $form['conditions'] = [
      'conditions_tabs' => [
        '#type' => 'vertical_tabs',
        '#title' => $this->t('Conditions'),
        '#description' => $this->t('All the conditions must be met before a rule is applied.'),
        '#parents' => [
          'conditions_tabs',
        ],
      ],
    ];

    // Get all available plugins from the plugin manager.
    foreach ($this->condition_plugin_manager->getDefinitionsForContexts($form_state->getTemporaryValue('gathered_contexts')) as $condition_id => $definition) {
      // If this condition exists already on the rule, use that.
      if ($rule->getConditions()->has($condition_id)) {
        $condition = $rule->getConditions()->get($condition_id);
      }
      else {
        $condition = $this->condition_plugin_manager->createInstance($condition_id, []);
      }

      // Set conditions in the form state for extraction later.
      $form_state->set(['conditions', $condition_id], $condition);

      // Allow condition plugins to build their own forms.
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'conditions_tabs';
      $form['conditions'][$condition_id] = $condition_form;
    }

    // Custom form alters for core conditions (lifted from BlockForm.php).
    if (isset($form['conditions']['node_type'])) {
      $form['conditions']['node_type']['#title'] = $this->t('Content types');
      $form['conditions']['node_type']['bundles']['#title'] = $this->t('Content types');
      $form['conditions']['node_type']['negate']['#type'] = 'value';
      $form['conditions']['node_type']['negate']['#title_display'] = 'invisible';
      $form['conditions']['node_type']['negate']['#value'] = $form['conditions']['node_type']['negate']['#default_value'];
    }
    if (isset($form['conditions']['user_role'])) {
      $form['conditions']['user_role']['#title'] = $this->t('Roles');
      unset($form['conditions']['user_role']['roles']['#description']);
      $form['conditions']['user_role']['negate']['#type'] = 'value';
      $form['conditions']['user_role']['negate']['#value'] = $form['conditions']['user_role']['negate']['#default_value'];
    }
    if (isset($form['conditions']['current_theme'])) {
      $form['conditions']['current_theme']['theme']['#empty_value'] = '';
      $form['conditions']['current_theme']['theme']['#empty_option'] = $this->t('- Any -');
    }
    if (isset($form['conditions']['request_path'])) {
      $form['conditions']['request_path']['#title'] = $this->t('Pages');
      $form['conditions']['request_path']['negate']['#type'] = 'radios';
      $form['conditions']['request_path']['negate']['#default_value'] = (int) $form['conditions']['request_path']['negate']['#default_value'];
      $form['conditions']['request_path']['negate']['#title_display'] = 'invisible';
      $form['conditions']['request_path']['negate']['#options'] = [
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      ];
    }
    if (isset($form['conditions']['language'])) {
      $form['conditions']['language']['negate']['#type'] = 'value';
      $form['conditions']['language']['negate']['#value'] = $form['conditions']['language']['negate']['#default_value'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Don't allow the user to select a menu name instead of a menu item.
    list($menu_name, $parent) = explode(':', $form_state->getValue('parent'));
    if (empty($parent)) {
      $form_state->setErrorByName('parent', $this->t('Please select a menu item. You have selected the name of a menu.'));
    }

    // Validate visibility condition settings.
    foreach ($form_state->getValue('conditions') as $condition_id => $values) {
      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $form_state->setValue(['conditions', $condition_id, 'negate'], (bool) $values['negate']);
      }

      // Allow the condition to validate the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->validateConfigurationForm($form['conditions'][$condition_id], SubformState::createForSubform($form['conditions'][$condition_id], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Break apart parent selector for menu link creation.
    $link_parts = explode(':', $form_state->getValue('parent'));
    $menu_name = array_shift($link_parts);
    $parent = implode(':', $link_parts);

    // @todo Add storage and get/set methods for these attributes.
    $form_state->setValue('menu_name', $menu_name);
    $form_state->setValue('parent', $parent);

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Get menu position rule.
    /* @var \Drupal\menu_position\Entity\MenuPositionRule $rule */
    $rule = $this->entity;
    $is_new = $rule->isNew();

    $menu_link_id = 'menu_position_link:' . $rule->id();
    if (!$this->menu_link_manager->hasDefinition($menu_link_id)) {
      $rule->setMenuLink($menu_link_id);
      $rule->save();
      // Let the deriver generate the menu link.
      $this->menu_link_manager->rebuild();
    }
    $definition = $this->menu_link_manager->getDefinition($menu_link_id);
    $menu_link = $this->menu_link_manager->updateDefinition($menu_link_id, [
      'menu_name' => $form_state->getValue('menu_name'),
      'parent' => $form_state->getValue('parent'),
    ] + $definition);

    // Submit visibility condition settings.
    foreach ($form_state->getValue('conditions') as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition_values = SubformState::createForSubform($form['conditions'][$condition_id], $form, $form_state);
      $condition->submitConfigurationForm($form['conditions'][$condition_id], $condition_values);

      // Set context mapping values.
      if ($condition instanceof ContextAwarePluginInterface) {
        $context_mapping = isset($values['context_mapping']) ? $values['context_mapping'] : [];
        $condition->setContextMapping($context_mapping);
      }

      // Update the original form values.
      $condition_configuration = $condition->getConfiguration();

      // Update the conditions on the menu position rule.
      $rule->getConditions()->addInstanceId($condition_id, $condition_configuration);
    }

    // Save the menu position rule and get the status for messaging.
    $status = $rule->save();
    if ($status && $is_new) {
      $this->messenger->addMessage($this->t('Rule %label has been added.', ['%label' => $rule->getLabel()]));
    }
    elseif ($status) {
      $this->messenger->addMessage($this->t('Rule %label has been updated.', ['%label' => $rule->getLabel()]));
    }
    else {
      $this->messenger->addWarning($this->t('Rule %label was not saved.', ['%label' => $rule->getLabel()]));
    }

    // Flush appropriate menu cache.
    $this->route_builder->rebuild();

    // Redirect back to the menu position rule order form.
    $form_state->setRedirect('entity.menu_position_rule.order_form');
  }

  /**
   * Returns boolean indicating whether or not this entity exists.
   *
   * @param string $id
   *   The id of the entity.
   *
   * @return bool
   *   Whether or not the entity exists already.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager
      ->getStorage('menu_position_rule')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
