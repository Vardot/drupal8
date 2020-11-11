<?php

namespace Drupal\Tests\lb_ux\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * Tests the section selection UI.
 *
 * @group lb_ux
 */
class SectionSelectionTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lb_ux',
    'node',
    'lb_ux_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable Layout Builder for one content type.
    $this->createContentType(['type' => 'bundle_with_section_field']);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->drupalLogin($this->createUser([
      'administer node display',
      'configure any layout',
      'access site reports',
    ]));
  }

  /**
   * @covers \Drupal\lb_ux\Controller\ConfigureSectionController::build
   */
  public function testBypassSectionConfiguration() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // On initial page load, neither of the test layouts are present.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default/layout');
    $assert_session->elementNotExists('css', '.layout--lb-ux-test-form-no-validation');
    $assert_session->elementNotExists('css', '.layout--lb-ux-test-form-with-validation');

    // Add a layout with no failing validation, bypassing the config form.
    $page->clickLink('Add section');
    $assert_session->assertWaitOnAjaxRequest();

    $page->clickLink('LB UX form no validation');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.layout--lb-ux-test-form-no-validation');
    $assert_session->pageTextNotContains('Check 1 2');

    $page->clickLink('Configure Section 1');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Check 1 2');

    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();

    // Add a layout with failing validation.
    $page->clickLink('Add section');
    $assert_session->assertWaitOnAjaxRequest();

    $page->clickLink('LB UX form with validation');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '.layout--lb-ux-test-form-with-validation');
    // The error message from the failed validation is not visible.
    $assert_session->pageTextNotContains("That's not the magic word!");

    // Subsequent failed form validation does show the error message.
    $page->pressButton('Add section');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '.layout--lb-ux-test-form-with-validation');
    $assert_session->pageTextContains("That's not the magic word!");

    // Fixing the validation error allows the form to be submitted.
    $page->fillField('label', 'Abracadabra');
    $page->pressButton('Add section');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.layout--lb-ux-test-form-with-validation');
  }

}
