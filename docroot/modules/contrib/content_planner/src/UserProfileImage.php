<?php

namespace Drupal\content_planner;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\user\UserInterface;

/**
 * Class UserProfileImage.
 */
class UserProfileImage {

  /**
   * Helper method that generate image url of the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   User entity.
   * @param string $image_style
   *   Image style ID.
   *
   * @return bool|string
   *   Image url or FALSE on failure.
   */
  public static function generateProfileImageUrl(UserInterface $user, $image_style) {

    if (
      ($user_picture_field = $user->get('user_picture')->getValue()) &&
      // Get file entity id.
      ($image_file_id = $user_picture_field[0]['target_id']) &&
      // Load File entity.
      ($file_entity = File::load($image_file_id)) &&
      // Load Image Style.
      ($style = ImageStyle::load($image_style))
    ) {
      // Build image style url.
      return $style->buildUrl($file_entity->getFileUri());
    }

    return FALSE;
  }

}
