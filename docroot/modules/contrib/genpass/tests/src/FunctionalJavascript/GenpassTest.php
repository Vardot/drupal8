<?php

namespace Drupal\Tests\genpass\FunctionalJavascript;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Generate Password .
 *
 * @group Genpass
 */
class GenpassTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'toolbar',
    'genpass',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with "administer account settings" .
   *
   * And "administer users" permissions.
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
      'access toolbar',
      'view the administration theme',
      'administer account settings',
      'administer users',
    ];

    $this->webUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->webUser);

  }

  /**
   * Test Generate Password configs and create users by admin.
   */
  public function testGenpassConfigsAndCreateUsersByAdmin() {

    // Configure Account settings with Generate Password options.
    $this->drupalGet('admin/config/people/accounts');
    $this->assertSession()->pageTextContains($this->t('Account settings'));
    $this->assertSession()->pageTextContains($this->t('Password handling'));
    $this->assertSession()->pageTextContains($this->t('Generated password length'));
    $this->assertSession()->pageTextContains($this->t('Password generation algorithm'));
    $this->assertSession()->pageTextContains($this->t('Generated password display'));

    $this->getSession()->getPage()->selectFieldOption('genpass_mode', '2');
    $this->getSession()->getPage()->pressButton($this->t('Save configuration'));

    $this->assertSession()->pageTextContains($this->t('The configuration options have been saved.'));

    // Create the test_authenticated user.
    $this->drupalGet('admin/people/create');
    $this->assertSession()->pageTextContains($this->t('Add user'));
    $this->getSession()->getPage()->fillField('mail', 'authenticated.test@drupal.org');
    $this->getSession()->getPage()->fillField('Username', 'test_authenticated');
    $this->getSession()->getPage()->pressButton('Create new account');
    $this->assertSession()->pageTextContains($this->t('Since you did not provide a password, it was generated automatically for this account.'));
    $this->assertSession()->pageTextContains($this->t('Created a new user account for test_authenticated. No email has been sent.'));

  }

}
