<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;


use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\ckeditor\Traits\CKEditorAdminSortTrait;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * @coversDefaultClass \Drupal\media_library\Plugin\CKEditorPlugin\DrupalMediaLibrary
 * @group media_library
 */
class ModalTest extends WebDriverTestBase {

  use CKEditorTestTrait;
  use CKEditorAdminSortTrait;
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The media item to embed.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor',
    'media_library',
    'node',
    'text',
    'media_library_test'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'media_embed' => ['status' => TRUE],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'rows' => [
            [
              [
                'name' => 'Main',
                'items' => [
                  'Source',
                  'Undo',
                  'Redo',
                ],
              ],
            ],
            [
              [
                'name' => 'Embeds',
                'items' => [
                  'DrupalMediaLibrary',
                ],
              ],
            ],
          ],
        ],
      ],
    ])->save();

    $this->drupalCreateContentType(['type' => 'blog']);

    // Note that media_install() grants 'view media' to all users by default.
    $this->user = $this->drupalCreateUser([
      'use text format test_format',
      'access media overview',
      'create blog content',
    ]);

    // Create a media type that starts with the letter a, to test tab order.
    $this->createMediaType('image', ['id' => 'arrakis', 'label' => 'Arrakis']);

    // Create a sample media entity to be embedded.
    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);
    File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ])->save();
    $this->media = Media::create([
      'bundle' => 'image',
      'name' => 'Fear is the mind-killer',
      'field_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $this->media->save();

    $arrakis_media = Media::create([
      'bundle' => 'arrakis',
      'name' => 'Le baron Vladimir Harkonnen',
      'field_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'Il complote pour détruire le duc Leto',
          'title' => 'Il complote pour détruire le duc Leto',
        ],
      ],
    ]);
    $arrakis_media->save();

    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the Media library insert button in CKEDITOR also works in modal.
   */
  public function testCkeditorInModal() {
    $this->drupalGet('/media-library-test-modal');
    $assert_session = $this->assertSession();
    $this->click('.modal-blog');
    $assert_session->waitForElement('css', '.ui-dialog-content');
    $instance_id = $assert_session->elementExists('css', '[data-drupal-selector="edit-body-0-value"]')->getAttribute('id');
    $this->waitForEditor($instance_id);
    $this->pressEditorButton('drupalmedialibrary', $instance_id);
    $this->assertNotEmpty($assert_session->waitForId('media-library-content'));
    $assert_session->pageTextContains('0 of 1 item selected');
    $assert_session->elementExists('css', '.js-media-library-item')->click();
    $assert_session->pageTextContains('1 of 1 item selected');
    $assert_session->elementExists('css', '.media-library-widget-modal .ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assignNameToCkeditorIframe('ckeditor', $instance_id);
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media .media', 2000));
  }

}
