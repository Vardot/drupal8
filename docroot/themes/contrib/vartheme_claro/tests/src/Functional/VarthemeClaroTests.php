<?php

namespace Drupal\Tests\vartheme_claro\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Cache\Cache;

/**
 * Vartheme Claro Tests.
 *
 * @group vartheme_claro
 */
class VarthemeClaroTests extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'toolbar',
    'language',
    'config_translation',
    'content_translation',
    'locale',
    'node',
    'system',
    'user',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    \Drupal::service('theme_installer')->install(['vartheme_claro']);

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('admin', 'vartheme_claro')
      ->save();

    ConfigurableLanguage::createFromLangcode('ar')->save();
    Cache::invalidateTags(['rendered', 'locale']);
  }

  /**
   * Check Vartheme Claro blocks.
   */
  public function testCheckVarthemeClaroBlocks() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Vartheme Claro blocks.
    $this->drupalGet('/admin/structure/block/list/vartheme_claro');

    $assert_session->pageTextContains($this->t('Page title'));
    $assert_session->pageTextContains($this->t('Primary tabs'));
    $assert_session->pageTextContains($this->t('Secondary tabs'));
    $assert_session->pageTextContains($this->t('Breadcrumbs'));
    $assert_session->pageTextContains($this->t('Status messages'));
    $assert_session->pageTextContains($this->t('Help'));
    $assert_session->pageTextContains($this->t('Primary admin actions'));
    $assert_session->pageTextContains($this->t('Main page content'));
  }

}
