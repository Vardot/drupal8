<?php

namespace Drupal\Tests\styleguide\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test basic functionality of Styleguide.
 *
 * @group styleguide
 */
class StyleguideTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['styleguide'];

  /**
   * Make sure the site works and Styleguite pages are accessible.
   */
  public function testStyleguide() {

    $this->drupalGet('/admin/appearance/styleguide');
    $this->assertSession()->titleEquals('Access denied | Drupal');

    $this->drupalGet('/admin/appearance/styleguide/maintenance-page');
    $this->assertSession()->titleEquals('Access denied | Drupal');

    $account = $this->drupalCreateUser(['view style guides']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/appearance/styleguide');
    $this->assertSession()->titleEquals('Style guide | Drupal');

    $this->assertSession()->pageTextContains('Showing style guide for Stark');

    $this->drupalGet('/admin/appearance/styleguide/maintenance-page');
    $this->assertSession()->statusCodeEquals(200);
  }

}
