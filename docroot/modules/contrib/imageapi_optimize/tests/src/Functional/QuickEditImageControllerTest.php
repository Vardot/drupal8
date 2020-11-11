<?php

namespace Drupal\Tests\imageapi_optimize\Functional;

use Drupal\Tests\image\Functional\QuickEditImageControllerTest as OriginalQuickEditImageControllerTest;

/**
 * Tests the endpoints used by the "image" in-place editor.
 *
 * @group imageapi_optimize
 */
class QuickEditImageControllerTest extends OriginalQuickEditImageControllerTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
