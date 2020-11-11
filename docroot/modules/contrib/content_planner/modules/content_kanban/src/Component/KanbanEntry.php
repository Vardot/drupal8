<?php

namespace Drupal\content_kanban\Component;

use Drupal\content_kanban\Form\SettingsForm;
use Drupal\content_kanban\EntityTypeConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\user\Entity\User;

/**
 * Class KanbanEntry.
 *
 * @package Drupal\content_kanban\Component
 */
class KanbanEntry {

  /**
   * The entity object.
   *
   * @var object
   */
  protected $entity;

  /**
   * The entity type config.
   *
   * @var \Drupal\content_kanban\EntityTypeConfig
   */
  protected $entityTypeConfig;

  /**
   * Internal cache for user pictures, used to avoid performance issues.
   *
   * @var array
   */
  static public $userPictureCache = [];

  /**
   * The immutable config to store existing configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content moderation status.
   *
   * @var string
   */
  protected $contentModerationStatus;

  /**
   * KanbanEntry constructor.
   *
   * @param object $entity
   *   The entity object.
   * @param string $content_moderation_status
   *   A string representing the current content moderation status.
   * @param \Drupal\content_kanban\EntityTypeConfig $entity_type_config
   *   The entity type config object.
   */
  public function __construct(
    $entity,
    $content_moderation_status,
    EntityTypeConfig $entity_type_config
  ) {
    $this->entity = $entity;
    $this->contentModerationStatus = $content_moderation_status;
    $this->entityTypeConfig = $entity_type_config;
    $this->config = \Drupal::config(SettingsForm::$configName);
  }

  /**
   * Builds the Kanban Entry output.
   */
  public function build() {
    $build = [];
    // Add time format to Entity.
    $datetime = new \DateTime();
    // Get the entity type and its keys.
    $entityType = \Drupal::entityTypeManager()->getStorage($this->entityTypeConfig->getEntityType());

    if ($entityType instanceof EntityStorageInterface) {

      $entityKeys = $entityType->getEntityType()->getKeys();
      $entityId = $entityKeys['id'];

      // Set needed info on the object.
      $this->entity->time = $datetime->format('H:i');
      $this->entity->entityLoaded = $entityType->load($this->entity->$entityId);

      // Get User Picture.
      $user_picture = $this->getUserPictureUrl($entityKeys['uid']);
      $build = [
        '#theme' => 'content_kanban_column_entry',
        '#entity' => $this->entity,
        '#entity_id' => $this->entity->$entityId,
        '#entity_type' => $this->entityTypeConfig->getEntityType(),
        '#entity_type_config' => $this->entityTypeConfig,
        '#user_picture' => $user_picture,
        '#workflow_state' => $this->contentModerationStatus,
        '#operation_links' => [
          'add' => $this->entity->entityLoaded->toUrl(),
          'edit' => $this->entity->entityLoaded->toUrl('edit-form'),
          'delete' => $this->entity->entityLoaded->toUrl('delete-form'),
        ],
        '#item_options' => [
          'background_color' => ($this->entity->entityLoaded->isPublished()) ? 'white' : '#fff4f4',
        ],
      ];
    }
    return $build;
  }

  /**
   * Gets the URL of the user picture.
   *
   * @param int $uid
   *   The user id.
   *
   * @return bool|string
   *   Returns the user picture url if any, FALSE otherwise.
   */
  protected function getUserPictureUrl($uid) {

    // If show user thumb is active.
    if ($this->config->get('show_user_thumb')) {

      $style_url = FALSE;

      // If a user picture is not in the internal cache, then create one.
      if (!array_key_exists($this->entity->$uid, self::$userPictureCache)) {

        // Load User.
        if ($user = User::load($this->entity->$uid)) {

          // Get user picture value.
          if ($user_picture_field = $user->get('user_picture')->getValue()) {

            // Get file entity id.
            if ($image_file_id = $user_picture_field[0]['target_id']) {

              // Load File entity.
              if ($file_entity = File::load($image_file_id)) {

                // Load Image Style.
                if ($style = ImageStyle::load('content_kanban_user_thumb')) {

                  // Build image style url.
                  $style_url = $style->buildUrl($file_entity->getFileUri());
                }

              }

            }

          }

        }

        // Store in Cache.
        self::$userPictureCache[$this->entity->$uid] = $style_url;
      }

      return self::$userPictureCache[$this->entity->$uid];
    }

    return FALSE;
  }

}
