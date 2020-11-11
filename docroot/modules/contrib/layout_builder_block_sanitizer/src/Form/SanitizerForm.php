<?php

namespace Drupal\layout_builder_block_sanitizer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_builder_block_sanitizer\LayoutBuilderBlockSanitizerManager;
use Drupal\layout_builder_block_sanitizer\LayoutBuilderBlockSanitizerBatch;

/**
 * Class SanitizerForm.
 */
class SanitizerForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The layout builder block sanitizer manager.
   *
   * @var Drupal\layout_builder_block_sanitizer\LayoutBuilderBlockSanitizerManager
   */
  protected $layoutBuilderBlockSanitizerManager;

  /**
   * The layout builder block sanitizer batch class.
   *
   * @var Drupal\layout_builder_block_sanitizer\LayoutBuilderBlockSanitizerBatch
   */
  protected $layoutBuilderBlockSanitizerBatch;

  /**
   * Constructs a new SanitizerForm object.
   */
  public function __construct(
    LayoutBuilderBlockSanitizerManager $layout_builder_block_sanitizer_manager,
    LayoutBuilderBlockSanitizerBatch $layout_builder_block_sanitizer_batch
  ) {
    $this->layoutBuilderBlockSanitizerManager = $layout_builder_block_sanitizer_manager;
    $this->layoutBuilderBlockSanitizerBatch = $layout_builder_block_sanitizer_batch;
  }

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder_block_sanitizer.manager'),
      $container->get('layout_builder_block_sanitizer.batch')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sanitizer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['individual_node_sanitize'] = [
      '#type' => 'fieldset',
      '#title' => 'Individual node sanitization',
    ];
    $form['individual_node_sanitize']['node_to_sanitize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node to sanitize'),
      '#description' => $this->t('Enter a node ID to sanitize non-existent blocks from it. Be sure to clear caches if blocks have recently been created.'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['individual_node_sanitize']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sanitize a single node'),
    ];

    $form['bulk_node_sanitize'] = [
      '#type' => 'fieldset',
      '#title' => 'Bulk node sanitization',
    ];
    $form['bulk_node_sanitize']['sanitize_all_nodes'] = [
      '#type' => 'submit',
      '#submit' => [
        '::batchSanitizeAllNodesStart',
      ],
      '#value' => 'Sanitize all nodes via batch',
      '#description' => $this->t('Note that caches will be cleared during this process automatically.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nid_to_sanitize = $form_state->getValue('node_to_sanitize');
    $this->layoutBuilderBlockSanitizerManager->sanitizeNode($nid_to_sanitize);
  }

  /**
   * Kick off batch process to sanitize all nodes on site.
   */
  public function batchSanitizeAllNodesStart(array &$form, FormStateInterface $form_state) {
    $this->layoutBuilderBlockSanitizerBatch->batchSanitizeAllNodesStart();
  }

}
