<?php

namespace Drupal\styleguide\Plugin\Styleguide;

use Drupal\comment\Entity\Comment;
use Drupal\styleguide\GeneratorInterface;
use Drupal\styleguide\Plugin\StyleguidePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Comment Styleguide items implementation.
 *
 * @Plugin(
 *   id = "comment_styleguide",
 *   label = @Translation("Comment Styleguide elements")
 * )
 */
class CommentStyleguide extends StyleguidePluginBase {

  /**
   * The styleguide generator service.
   *
   * @var \Drupal\styleguide\Generator
   */
  protected $generator;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new CommentStyleguide.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\styleguide\GeneratorInterface $styleguide_generator
   *   The styleguide generator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, GeneratorInterface $styleguide_generator, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->generator = $styleguide_generator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('styleguide.generator'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function items() {
    $items = [];
    if ($this->moduleHandler->moduleExists('comment')) {
      $items['comment'] = [
        'title' => $this->t('Comment'),
        'content' => $this->commentPrepare(),
        'group' => $this->t('Comment'),
      ];
    }

    return $items;
  }

  /**
   * Helper method to prepare a fake comment.
   *
   * @return array
   *   A renderable array.
   */
  private function commentPrepare() {
    $comment = [
      'subject' => $this->generator->words(5),
      'comment_type' => 'comment',
      'cid' => 0,
    ];

    return [
      '#theme' => 'comment',
      '#comment' => Comment::create($comment),
      '#comment_threaded' => TRUE,
      'content' => $this->generator->paragraphs(),
    ];
  }

}
