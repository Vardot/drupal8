<?php

namespace Drupal\Tests\varbase_api\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests Varbase API Settings test.
 *
 * @group varbase_api
 */
class VarbaseApiSettingsTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['varbase_api'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Check Varbase API Settings.
   */
  public function testCheckVarbaseApiSettings() {
    $assert_session = $this->assertSession();

    // Varbase API settings.
    $this->drupalGet('/admin/config/system/varbase/api');

    $varbase_api_settings_text = $this->t('Varbase API settings');
    $assert_session->pageTextContains($varbase_api_settings_text);

    $expose_view_json_text = $this->t('Expose a "View JSON" link in entity operations');
    $assert_session->pageTextContains($expose_view_json_text);

    $expose_view_api_doc_text = $this->t('Expose a "View API Documentation" link in bundle entity operations');
    $assert_session->pageTextContains($expose_view_api_doc_text);

    // Generate keys.
    $this->drupalGet('/admin/config/system/varbase/api/keys');

    $generate_keys_text = $this->t('Generate keys');
    $assert_session->pageTextContains($generate_keys_text);

    $destination_text = $this->t('Destination');
    $assert_session->pageTextContains($destination_text);

  }

}
