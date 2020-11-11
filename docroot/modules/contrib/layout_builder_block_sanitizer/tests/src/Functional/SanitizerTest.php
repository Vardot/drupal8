<?php

namespace Drupal\Tests\layout_builder_block_sanitizer\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\block_content\Entity\BlockContentType;

/**
 * Tests block sanitization.
 *
 * @group layout_builder_block_sanitizer
 */
class SanitizerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'layout_builder',
    'block',
    'block_content',
    'node',
    'layout_builder_block_sanitizer',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->strictConfigSchema = NULL;
    parent::setUp();

    // Create basic block.
    $label = 'basic';
    $bundle = BlockContentType::create([
      'id' => $label,
      'label' => $label,
      'revision' => FALSE,
    ]);
    $bundle->save();
    block_content_add_body_field($bundle->id());

    // Create content type w/ layout builder enabled.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
    ]);
    $this->createNode(['type' => 'bundle_with_section_field']);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer display modes',
      'use layout builder block sanitizer',
      'administer blocks',
    ], 'foobar'));
  }

  /**
   * Tests an individual node sanitization.
   */
  public function testSingleNodeSanitize() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field/display';

    // Enable Layout Builder for the default view modes, and overrides.
    $this->drupalGet("$field_ui_prefix/default");
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

    // Add a basic block.
    $this->drupalGet('block/add/basic');
    $block_form = [
      'edit-info-0-value' => 'A sample block',
      'edit-body-0-value' => 'Some sample block content in the body',
    ];
    $this->submitForm($block_form, 'Save');

    // Place block on page.
    $this->drupalGet('node/1/layout');
    $page->clickLink('Add Block');
    $page->clickLink('A sample block');
    $page->pressButton('Add Block');
    $page->pressButton('Save layout');
    $assert_session->pageTextContains('Some sample block content in the body');

    // Delete the block.
    $this->drupalGet("block/1/delete");
    $page->pressButton('Delete');

    // Verify it's broken on the node.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This block is broken or missing. You may be missing content or you might need to enable the original module.');

    // Clear caches.
    drupal_flush_all_caches();

    // Run sanitization.
    $this->drupalGet("admin/structure/lbbs/sanitizer");
    $sanitize_form = [
      'edit-node-to-sanitize' => '1',
    ];
    $this->submitForm($sanitize_form, 'Sanitize a single node');

    // Verify error block no longer on the node.
    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains('This block is broken or missing. You may be missing content or you might need to enable the original module.');
  }

}
