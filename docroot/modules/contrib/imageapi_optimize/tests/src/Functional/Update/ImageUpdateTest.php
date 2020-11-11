<?php

namespace Drupal\Tests\imageapi_optimize\Functional\Update;

use Drupal\Tests\image\Functional\Update\ImageUpdateTest as OriginalImageUpdateTest;

/**
 * Tests Image update path.
 *
 * @group imageapi_optimize
 */
class ImageUpdateTest extends OriginalImageUpdateTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
