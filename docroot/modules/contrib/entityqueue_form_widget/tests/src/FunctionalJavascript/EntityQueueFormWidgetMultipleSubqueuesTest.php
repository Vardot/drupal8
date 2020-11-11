<?php

namespace Drupal\Tests\entityqueue_form_widget\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entityqueue\Entity\EntitySubqueue;

/**
 * Tests the UI for Entity Queue Form Widget with Multiple Subqueues.
 *
 * @group entityqueue_form_widget
 */
class EntityQueueFormWidgetMultipleSubqueuesTest extends WebDriverTestBase {

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

    $subqueue = EntitySubqueue::create([
      'queue' => 'test_multiple_subqueues',
      'name' => 'subqueue_1',
      'title' => $this->t('Subqueue 1'),
    ]);
    $subqueue->save();

    $subqueue = EntitySubqueue::create([
      'queue' => 'test_multiple_subqueues',
      'name' => 'subqueue_2',
      'title' => $this->t('Subqueue 2'),
    ]);
    $subqueue->save();

    $subqueue = EntitySubqueue::create([
      'queue' => 'test_multiple_subqueues',
      'name' => 'subqueue_3',
      'title' => $this->t('Subqueue 3'),
    ]);
    $subqueue->save();

    $permissions = [
      'view the administration theme',
      'administer nodes',
      'create queued_content content',
      'edit any queued_content content',
      'manipulate all entityqueues',
    ];

    $this->webUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->webUser);

  }

  /**
   * Tests Entity Queue Form Widget with multiple sub queues.
   */
  public function testEntityQueueFormWidgetMultipleSubqueues() {

    $this->drupalGet('/node/add/queued_content');
    $this->assertSession()->waitForElementVisible('css', '#edit-entityqueue-form-widget');
    $entityqeues_settings_text = $this->t('Entityqueues settings');
    $this->assertSession()->pageTextContains($entityqeues_settings_text);
    $this->clickLink($entityqeues_settings_text);

    $test_multiple_subqueues_1_text = $this->t("Subqueue 1 (Test Multiple subqueues)");
    $test_multiple_subqueues_2_text = $this->t("Subqueue 2 (Test Multiple subqueues)");
    $test_multiple_subqueues_3_text = $this->t("Subqueue 3 (Test Multiple subqueues)");

    $this->assertSession()->pageTextContains($test_multiple_subqueues_1_text);
    $this->assertSession()->pageTextContains($test_multiple_subqueues_2_text);
    $this->assertSession()->pageTextContains($test_multiple_subqueues_3_text);

  }

}
