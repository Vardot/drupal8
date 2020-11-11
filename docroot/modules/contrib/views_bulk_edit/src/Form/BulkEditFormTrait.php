<?php

namespace Drupal\views_bulk_edit\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Trait BulkEditFormTrait.
 */
trait BulkEditFormTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Builds the bundle forms.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $bundle_data
   *   An array with all entity types and their bundles.
   *
   * @return array
   *   The bundle forms.
   */
  public function buildBundleForms(array $form, FormStateInterface $form_state, array $bundle_data) {

    // Disable cache to avoid errors with storing files in tempstore.
    $form_state->disableCache();

    // Store entity data.
    $form_state->set('vbe_entity_bundles_data', $bundle_data);

    $form['#attributes']['class'] = ['views-bulk-edit-form'];
    $form['#attached']['library'][] = 'views_bulk_edit/views_bulk_edit.edit_form';

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
    ];
    $form['options']['_add_values'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add values to multi-value fields'),
      '#description' => $this->t('New values of multi-value fields will be added to the existing ones instead of overwriting them.'),
    ];

    $bundle_count = 0;
    foreach ($bundle_data as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle => $label) {
        $bundle_count++;
      }
    }

    foreach ($bundle_data as $entity_type_id => $bundles) {

      foreach ($bundles as $bundle => $label) {
        $form = $this->getBundleForm($entity_type_id, $bundle, $label, $form, $form_state, $bundle_count);
      }
    }

    return $form;
  }

  /**
   * Gets the form for this entity display.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle ID.
   * @param mixed $bundle_label
   *   Bundle label.
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   * @param int $bundle_count
   *   Number of bundles that may be affected.
   *
   * @return array
   *   Edit form for the current entity bundle.
   */
  protected function getBundleForm($entity_type_id, $bundle, $bundle_label, array $form, FormStateInterface $form_state, $bundle_count) {
    $entityType = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->create([
      $entityType->getKey('bundle') => $bundle,
    ]);

    if (!isset($form[$entity_type_id])) {
      $form[$entity_type_id] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];
    }

    // If there is no bundle label, the entity has no bundles.
    if (empty($bundle_label)) {
      $bundle_label = $entityType->getLabel();
    }
    $form[$entity_type_id][$bundle] = [
      '#type' => 'details',
      '#open' => ($bundle_count === 1),
      '#title' => $entityType->getLabel() . ' - ' . $bundle_label,
      '#parents' => [$entity_type_id, $bundle],
    ];

    $form_display = EntityFormDisplay::collectRenderDisplay($entity, 'bulk_edit');
    $form_display->buildForm($entity, $form[$entity_type_id][$bundle], $form_state);

    $form[$entity_type_id][$bundle] += $this->getSelectorForm($entity_type_id, $bundle, $form[$entity_type_id][$bundle]);

    return $form;
  }

  /**
   * Builds the selector form.
   *
   * Given an entity form, create a selector form to provide options to update
   * values.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle
   *   The bundle machine name.
   * @param array $form
   *   The form we're building the selection options for.
   *
   * @return array
   *   The new selector form.
   */
  protected function getSelectorForm($entity_type_id, $bundle, array &$form) {
    $selector['_field_selector'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select fields to change'),
      '#weight' => -50,
      '#tree' => TRUE,
      '#attributes' => ['class' => ['vbe-selector-fieldset']],
    ];

    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#access']) && !$form[$key]['#access']) {
        continue;
      }
      if ($key == '_field_selector' || !$element = &$this->findFormElement($form[$key])) {
        continue;
      }

      if (!$definitions[$key]->isDisplayConfigurable('form')) {
        $element['#access'] = FALSE;
        continue;
      }

      // Modify the referenced element a bit so it doesn't
      // cause errors and returns correct data structure.
      $element['#required'] = FALSE;
      $element['#tree'] = TRUE;

      // Add the toggle field to the form.
      $selector['_field_selector'][$key] = [
        '#type' => 'checkbox',
        '#title' => $element['#title'],
        '#weight' => isset($form[$key]['#weight']) ? $form[$key]['#weight'] : 0,
        '#tree' => TRUE,
      ];

      // Force the original value to be hidden unless the checkbox is enabled.
      $form[$key]['#states'] = [
        'visible' => [
          sprintf('[name="%s[%s][_field_selector][%s]"]', $entity_type_id, $bundle, $key) => ['checked' => TRUE],
        ],
      ];
    }

    if (empty(Element::children($selector['_field_selector']))) {
      $selector['_field_selector']['#title'] = $this->t('There are no fields available to modify');
    }

    return $selector;
  }

  /**
   * Finds the deepest most form element and returns it.
   *
   * @param array $form
   *   The form element we're searching.
   * @param string $title
   *   The most recent non-empty title from previous form elements.
   *
   * @return array|null
   *   The deepest most element if we can find it.
   */
  protected function &findFormElement(array &$form, $title = NULL) {
    $element = NULL;
    foreach (Element::children($form) as $key) {
      // Not all levels have both #title and #type.
      // Attempt to inherit #title from previous iterations.
      // Some #titles are empty strings.  Ignore them.
      if (!empty($form[$key]['#title'])) {
        $title = $form[$key]['#title'];
      }
      elseif (!empty($form[$key]['title']['#value']) && !empty($form[$key]['title']['#type']) && $form[$key]['title']['#type'] === 'html_tag') {
        $title = $form[$key]['title']['#value'];
      }
      if (isset($form[$key]['#type']) && !empty($title)) {
        // Fix empty or missing #title in $form.
        if (empty($form[$key]['#title'])) {
          $form[$key]['#title'] = $title;
        }
        $element = &$form[$key];
        break;
      }
      elseif (is_array($form[$key])) {
        $element = &$this->findFormElement($form[$key], $title);
      }
    }
    return $element;
  }

  /**
   * Provides same functionality as ARRAY_FILTER_USE_KEY for PHP 5.5.
   *
   * @param array $array
   *   The array of data to filter.
   * @param callable $callback
   *   The function we're going to use to determine the filtering.
   *
   * @return array
   *   The filtered data.
   */
  protected function filterOnKey(array $array, callable $callback) {
    $filtered_values = [];
    foreach ($array as $key => $value) {
      if ($callback($key)) {
        $filtered_values[$key] = $value;
      }
    }
    return $filtered_values;
  }

  /**
   * Save modified entity field values to action configuration.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $bundle_data = $storage['vbe_entity_bundles_data'];

    foreach ($bundle_data as $entity_type_id => $bundles) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      foreach ($bundles as $bundle => $label) {
        $field_data = $form_state->getValue([$entity_type_id, $bundle]);
        $modify = array_filter($field_data['_field_selector']);
        if (!empty($modify)) {
          $form_clone = $form;
          $form_clone['#parents'] = [$entity_type_id, $bundle];
          $entity = $this->entityTypeManager->getStorage($entity_type_id)->create([
            $entity_type->getKey('bundle') => $bundle,
          ]);
          $form_display = EntityFormDisplay::collectRenderDisplay($entity, 'bulk_edit');
          $form_display->extractFormValues($entity, $form_clone, $form_state);

          foreach (array_keys($modify) as $field) {
            $this->configuration[$entity_type_id][$bundle][$field] = $entity->{$field}->getValue();
          }
        }
      }
    }

    $this->configuration['_add_values'] = $form_state->getValue('_add_values');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    // Load the edit revision for safe editing.
    $entity = $this->entityRepository->getActive($type_id, $entity->id());

    $result = $this->t('Skip (field is not present on this bundle)');
    if (isset($this->configuration[$type_id][$bundle])) {
      $values = $this->configuration[$type_id][$bundle];
      foreach ($values as $field => $value) {
        if (!empty($this->configuration['_add_values'])) {
          /* @var \Drupal\Core\Field\FieldStorageDefinitionInterface $storageDefinition */
          $storageDefinition = $entity->{$field}->getFieldDefinition()->getFieldStorageDefinition();
          $cardinality = $storageDefinition->getCardinality();
          if ($cardinality === $storageDefinition::CARDINALITY_UNLIMITED || $cardinality > 1) {
            $current_value = $entity->{$field}->getValue();
            $value_count = count($current_value);
            foreach ($value as $item) {
              if ($cardinality != $storageDefinition::CARDINALITY_UNLIMITED && $value_count >= $cardinality) {
                break;
              }
              $current_value[] = $item;
            }
            $value = $current_value;
          }
        }

        $entity->{$field}->setValue($value);
      }

      // Set up revision defaults if entity is revisionable.
      if ($entity instanceof RevisionLogInterface) {
        $entity->setNewRevision();
        $entity->setRevisionCreationTime($this->time->getCurrentTime());
        $entity->setRevisionUserId($this->currentUser->id());
        if (empty($values['revision_log'][0]['value'])) {
          $entity->setRevisionLogMessage($this->formatPlural(count($values), 'Edited as a part of bulk operation. Field changed: @fields', 'Edited as a part of bulk operation. Fields changed: @fields', ['@fields' => implode(', ', array_keys($values))]));
        }
        else {
          $entity->setRevisionLogMessage($values['revision_log'][0]['value']);
        }
      }

      $entity->save();
      $result = $this->t('Modify field values');
    }
    return $result;
  }

}
