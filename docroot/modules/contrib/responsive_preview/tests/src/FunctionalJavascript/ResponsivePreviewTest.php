<?php

namespace Drupal\Tests\responsive_preview\FunctionalJavascript;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the toolbar integration.
 *
 * @group responsive_preview
 */
class ResponsivePreviewTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['responsive_preview', 'toolbar', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $previewUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->previewUser = $this->drupalCreateUser([
      'access responsive preview',
      'access toolbar',
      'view test entity',
      'administer entity_test content',
    ]);
  }

  /**
   * Tests that the toolbar integration works properly.
   */
  public function testToolbarIntegration() {
    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assert_session */
    $assert_session = $this->assertSession();

    /** @var \Behat\Mink\Session $session */
    $session = $this->getSession();

    $entity = EntityTest::create();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $this->drupalLogin($this->previewUser);

    $this->drupalGet($entity->toUrl());

    $this->selectDevice('(//*[@id="responsive-preview-toolbar-tab"]//button[@data-responsive-preview-name])[1]');
    $assert_session->elementNotExists('xpath', '//*[@id="responsive-preview-orientation" and contains(@class, "rotated")]');
    $this->assertTrue($session->evaluateScript("jQuery('#responsive-preview-frame')[0].contentWindow.location.href.endsWith('/entity_test/1')"));
  }

  /**
   * Select device for device preview.
   *
   * NOTE: Index starts from 1.
   *
   * @param int $xpath_device_button
   *   The index number of device in drop-down list.
   */
  protected function selectDevice($xpath_device_button) {
    $page = $this->getSession()->getPage();

    $page->find('xpath', '//*[@id="responsive-preview-toolbar-tab"]/button')
      ->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->find('xpath', $xpath_device_button)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

}
