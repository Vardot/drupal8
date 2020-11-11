<?php

namespace Drupal\Tests\lb_ux\FunctionalJavascript;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\layout_builder\FunctionalJavascript\InlineBlockTestBase;

/**
 * Tests creating blocks.
 *
 * @group lb_ux
 */
class BlockCreationTest extends InlineBlockTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'lb_ux',
    'node',
    'lb_ux_test',
    'block_content',
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
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->drupalLogin($this->createUser([
      'administer node display',
      'configure any layout',
      'create and edit custom blocks',
    ]));
  }

  /**
   * Tests the block form.
   */
  public function testBlockForm() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default/layout');
    $page->clickLink('Add block');
    $assert_session->assertWaitOnAjaxRequest();

    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->checkboxNotChecked('settings[label_display]');
    $label = $assert_session->fieldExists('settings[label]');
    $this->assertFalse($label->isVisible());
    $assert_session->fieldValueEquals('settings[label]', 'Basic block 1');

    $page->checkField('settings[label_display]');
    $this->assertTrue($label->isVisible());
    $assert_session->fieldValueEquals('settings[label]', 'Basic block 1');

    $page->uncheckField('settings[label_display]');

    $page->pressButton('Add block');
    $assert_session->assertWaitOnAjaxRequest();

    $page->clickLink('Add block');
    $assert_session->assertWaitOnAjaxRequest();

    // Add a second block.
    $this->clickLink('Create custom block');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add block');
    $assert_session->assertWaitOnAjaxRequest();

    $blocks = $page->findAll('css', '.layout-builder-block');
    $expected_labels = [
      '"Links" field',
      '"Body" field',
      '"Basic block 1" block',
      '"Basic block 2" block',
    ];
    $this->assertCount(count($expected_labels), $blocks);
    foreach ($blocks as $block) {
      $expected_label = array_shift($expected_labels);
      $block->mouseOver();
      $this->assertTrue((bool) $assert_session->waitForText($expected_label));
    }
  }

}
