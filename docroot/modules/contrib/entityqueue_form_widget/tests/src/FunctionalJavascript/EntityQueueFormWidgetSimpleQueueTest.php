<?php

namespace Drupal\Tests\entityqueue_form_widget\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests the UI for Entity Queue Form Widget with Simple Queue.
 *
 * @group entityqueue_form_widget
 */
class EntityQueueFormWidgetSimpleQueueTest extends WebDriverTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['field',
    'filter',
    'node',
    'text',
    'user',
    'system',
    'entityqueue',
    'entityqueue_form_widget',
    'entityqueue_form_widget_test',
  ];

  /**
   * A user with the 'manipulate all entityqueue' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'view the administration theme',
      'administer nodes',
      'create test_content content',
      'edit any test_content content',
      'manipulate all entityqueues',
    ];

    $this->webUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests Entity Queue Form Widget with Simple Queue.
   */
  public function testEntityQueueFormWidgetSimpleQueue() {
    $this->drupalGet('/node/add/test_content');
    $this->assertSession()->waitForElementVisible('css', '#edit-entityqueue-form-widget');
    $entityqeues_settings_text = $this->t('Entityqueues settings');
    $this->assertSession()->pageTextContains($entityqeues_settings_text);
    $this->clickLink($entityqeues_settings_text);

    $test_simple_queue_text = $this->t("Test Queue");
    $this->assertSession()->pageTextContains($test_simple_queue_text);

  }

}
