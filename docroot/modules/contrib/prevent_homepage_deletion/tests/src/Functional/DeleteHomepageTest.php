<?php

namespace Drupal\Tests\prevent_homepage_deletion\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of this module.
 *
 * @group prevent_homepage_deletion
 *
 */
class DeleteHomepageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'user', 'prevent_homepage_deletion'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Our node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * Our two nodes.
   * @var \Drupal\node\NodeInterface
   */
  protected $page_home;
  protected $page_not_home;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add a content type.
    $this->contentType = $this->createContentType(['type' => 'page']);
    // Create a first page (homepage).
    $this->page_home = $this->drupalCreateNode(['type' => 'page']);
    // Set page to be homepage.
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('page.front', '/node/'.$this->page_home->id())
      ->save(TRUE);
    // Create a second page (not homepage).
    $this->page_not_home = $this->drupalCreateNode(['type' => 'page']);
  }

  /**
   * Test to check if the homepage can be deleted by various users.
   */
  public function testDeleteHomepage() {
    // Step 1: Log in a user who can delete the homepage.
    $this->drupalLogin(
      $this->createUser([
        'delete any page content',
        'delete_homepage_node',
      ])
    );

    // Step 2: Try to delete the homepage.
    $this->drupalGet('node/' . $this->page_home->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);

    // Step 3: Logout, and login as user without the permission.
    $this->drupalLogout();
    $this->drupalLogin(
      $this->createUser([
        'delete any page content',
      ])
    );

    // Step 4: Try to delete the homepage.
    $this->drupalGet('node/' . $this->page_home->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);

    // Step 5: Try to delete the non-homepage.
    $this->drupalGet('node/' . $this->page_not_home->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);

  }

}
