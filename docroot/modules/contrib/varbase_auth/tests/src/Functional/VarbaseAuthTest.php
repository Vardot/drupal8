<?php

namespace Drupal\Tests\varbase_auth\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Varbase Auth test.
 *
 * @group varbase_auth
 */
class VarbaseAuthTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'varbase_auth',
    'social_auth',
    'social_auth_facebook',
    'social_auth_google',
    'social_auth_linkedin',
    'social_auth_twitter',
  ];

  /**
   * Check Varbase Auth Login page.
   */
  public function testCheckVarbaseAuthLoginPage() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/user/login');

    $assert_session->elementExists('css', '.block-social-auth-login');

  }

  /**
   * Check Varbase Auth User register.
   */
  public function testCheckVarbaseAuthUserRegister() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/user/register');

    $assert_session->elementExists('css', '.block-social-auth');

  }

  /**
   * Check Varbase Auth User authentication.
   */
  public function testCheckVarbaseAuthUserAuthentication() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/config/social-api/social-auth');
    $assert_session->pageTextContains('social_auth_facebook');
    $assert_session->pageTextContains('social_auth_google');
    $assert_session->pageTextContains('social_auth_linkedin');
    $assert_session->pageTextContains('social_auth_twitter');

  }

}
