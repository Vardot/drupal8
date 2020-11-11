<?php

namespace Drupal\Tests\views_bulk_edit\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Tests\node\Functional\Views\NodeTestBase;

/**
 * Tests the core edit action.
 *
 * @group views_bulk_edit
 */
class ViewsBulkEditActionTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views_bulk_edit', 'node_test_views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_node_bulk_form'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);
    $this->createContentType(['type' => 'page', 'name' => 'Page']);
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $admin = $this->createUser([
      'bypass node access',
      'access content overview',
      'access content',
      'administer content types',
      'use views bulk edit',
      // Likely a bug in core, NodeAccessControlHandler::checkFieldAccess().
      'administer nodes',
    ]);

    $this->drupalLogin($admin);
  }

  /**
   * Test VBE from the UI using the node module.
   */
  public function testViewsBulkEdit() {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $page1 = $this->createNode();
    $page2 = $this->createNode();
    $article1 = $this->createNode(['type' => 'article']);

    // Test editing a single article with properties and fields.
    $this->drupalGet('test-node-bulk-form');
    $this->submitForm([
      'action' => 'node_edit_action',
      'node_bulk_form[0]' => TRUE,
      'node_bulk_form[1]' => TRUE,
    ], 'Apply to selected items');

    $random_title = $this->randomMachineName();

    // Ensure that non configurable form fields do not appear.
    $this->assertSession()->fieldNotExists('node[page][_field_selector][revision_log]');

    $this->submitForm([
      'node[page][_field_selector][title]' => '1',
      'node[page][title][0][value]' => $random_title,
    ], 'Confirm');

    // Assert property was changes. Assert field was changed.
    $storage->resetCache();
    $nodes = array_values($storage->loadMultiple([
      $page1->id(),
      $page2->id(),
      $article1->id(),
    ]));
    $this->assertEquals($random_title, $nodes[0]->getTitle());
    $this->assertEquals($random_title, $nodes[1]->getTitle());
    $this->assertNotEquals($random_title, $nodes[2]->getTitle());
  }

  /**
   * Test editing an article and a page bundle.
   */
  public function testBulkEditMultipleBundles() {
    $page1 = $this->createNode();
    $article1 = $this->createNode(['type' => 'article']);
    $this->drupalGet('test-node-bulk-form');
    $this->submitForm([
      'action' => 'node_edit_action',
      'node_bulk_form[0]' => TRUE,
      'node_bulk_form[1]' => TRUE,
    ], 'Apply to selected items');

    $random_title = $this->randomMachineName();
    $this->submitForm([
      'node[page][_field_selector][title]' => '1',
      'node[page][title][0][value]' => $random_title,
      'node[article][_field_selector][title]' => '1',
      'node[article][title][0][value]' => $random_title,
    ], 'Confirm');

    // Assert property and field is changed.
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $storage->resetCache();
    $nodes = array_values($storage->loadMultiple([$page1->id(), $article1->id()]));
    $this->assertEquals($random_title, $nodes[0]->getTitle());
    $this->assertEquals($random_title, $nodes[1]->getTitle());
  }

  /**
   * Values that are not selected or displayed are never changed.
   */
  public function testOnlySelectedValuesAreChanged() {
    // Test submitting form with new fields for a field and a property but not
    // selecting them to be changed does not cause a change.
    $page1 = $this->createNode();
    $this->drupalGet('test-node-bulk-form');
    $this->submitForm([
      'action' => 'node_edit_action',
      'node_bulk_form[0]' => TRUE,
    ], 'Apply to selected items');

    $random_title = $this->randomMachineName();
    $this->submitForm([
      'node[page][title][0][value]' => $random_title,
    ], 'Confirm');
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $storage->resetCache();
    $this->assertNotEquals($random_title, $storage->load($page1->id())->getTitle());
  }

  /**
   * Test non-configured fields are not displayed.
   */
  public function testFieldsNotDisplayedAreIgnored() {
    EntityFormMode::create([
      'id' => 'node.bulk_edit',
      'label' => 'Bulk Edit',
      'targetEntityType' => 'node',
    ])->save();
    $display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'bulk_edit',
      'status' => TRUE,
    ]);

    $this->createNode();
    $this->drupalGet('test-node-bulk-form');
    $this->submitForm([
      'action' => 'node_edit_action',
      'node_bulk_form[0]' => TRUE,
    ], 'Apply to selected items');
    $this->assertSession()->fieldExists('node[page][_field_selector][title]');

    // Update the display to hide the title.
    $display
      ->removeComponent('title')
      ->save();

    // Node the title field should no longer be displayed.
    $this->drupalGet('test-node-bulk-form');
    $this->submitForm([
      'action' => 'node_edit_action',
      'node_bulk_form[0]' => TRUE,
    ], 'Apply to selected items');
    $this->assertSession()->fieldNotExists('node[page][_field_selector][title]');
  }

}
