<?php

namespace Drupal\Tests\imageapi_optimize\FunctionalJavascript;

use Drupal\Tests\image\FunctionalJavascript\QuickEditImageTest as OriginalQuickEditImageTest;

/**
 * Tests the JavaScript functionality of the "image" in-place editor.
 *
 * @coversDefaultClass \Drupal\image\Plugin\InPlaceEditor\Image
 * @group imageapi_optimize
 */
class QuickEditImageTest extends OriginalQuickEditImageTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
