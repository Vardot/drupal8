<?php

namespace Drupal\dropzonejs\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dropzonejs\DropzoneJsUploadSaveInterface;
use Drupal\media_library\Form\FileUploadForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a form to create media entities from uploaded files.
 */
class DropzoneJsUploadForm extends FileUploadForm {

  /**
   * DropzoneJS module upload save service.
   *
   * @var \Drupal\dropzonejs\DropzoneJsUploadSaveInterface
   */
  protected $dropzoneJsUploadSave;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->setDropzoneJsUploadSave($container->get('dropzonejs.upload_save'));
    return $form;
  }

  /**
   * Set the upload service.
   *
   * @param \Drupal\dropzonejs\DropzoneJsUploadSaveInterface $dropzoneJsUploadSave
   *   The upload service.
   */
  protected function setDropzoneJsUploadSave(DropzoneJsUploadSaveInterface $dropzoneJsUploadSave) {
    $this->dropzoneJsUploadSave = $dropzoneJsUploadSave;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    // Create a file item to get the upload validators.
    $media_type = $this->getMediaType($form_state);
    $item = $this->createFileItem($media_type);

    $state = $this->getMediaLibraryState($form_state);
    if (!$state->hasSlotsAvailable()) {
      return $form;
    }

    $slots = $state->getAvailableSlots();

    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
    ];

    $settings = $item->getFieldDefinition()->getSettings();

    $process = (array) $this->elementInfo->getInfoProperty('dropzonejs', '#process', []);
    $form['container']['upload'] = [
      '#type' => 'dropzonejs',
      '#title' => $this->formatPlural($slots, 'Add file', 'Add files'),
      // @todo Move validation in https://www.drupal.org/node/2988215
      '#process' => array_merge(['::validateUploadElement'], $process),
      '#max_files' => $slots < 1 ? 0 : $slots,
      '#max_filesize' => $settings['max_filesize'],
      '#extensions' => $settings['file_extensions'],
      '#remaining_slots' => $slots,
      '#required' => TRUE,
      '#dropzone_description' => $this->t('Drop files here to upload them'),
    ];

    $form['auto_select_handler'] = [
      '#type' => 'hidden',
      '#name' => 'auto_select_handler',
      '#id' => 'auto_select_handler',
      '#attributes' => ['id' => 'auto_select_handler'],
      '#submit' => ['::uploadButtonSubmit'],
      '#executes_submit_callback' => TRUE,
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        'event' => 'auto_select_media_library_widget',
        // Add a fixed URL to post the form since AJAX forms are automatically
        // posted to <current> instead of $form['#action'].
        // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
        // is fixed.
        // Follow along with changes in \Drupal\media_library\Form\OEmbedForm.
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];

    $form['#attached']['library'][] = 'dropzonejs/widget';
    $form['#attached']['library'][] = 'dropzonejs/media_library';

    $file_upload_help = [
      '#theme' => 'file_upload_help',
      '#upload_validators' => $form['container']['upload']['#upload_validators'],
      '#cardinality' => $slots,
    ];

    // The file upload help needs to be rendered since the description does not
    // accept render arrays. The FileWidget::formElement() method adds the file
    // upload help in the same way, so any theming improvements made to file
    // fields would also be applied to this upload field.
    // @see \Drupal\file\Plugin\Field\FieldWidget\FileWidget::formElement()
    $form['container']['upload']['#description'] = $this->renderer->renderPlain($file_upload_help);

    return $form;
  }

  /**
   * Submit handler for the upload button, inside the managed_file element.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function uploadButtonSubmit(array $form, FormStateInterface $form_state) {
    $files = $this->getFiles($form, $form_state);
    $this->processInputValues($files, $form, $form_state);
  }

  /**
   * Gets uploaded files.
   *
   * We implement this to allow child classes to operate on different entity
   * type while still having access to the files in the validate callback here.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\file\FileInterface[]
   *   Array of uploaded files.
   */
  protected function getFiles(array $form, FormStateInterface $form_state) {
    // Create a file item to get the upload validators.
    $media_type = $this->getMediaType($form_state);
    $item = $this->createFileItem($media_type);

    $settings = $item->getFieldDefinition()->getSettings();

    $additional_validators = ['file_validate_size' => [Bytes::toInt($settings['max_filesize']), 0]];

    $files = $form_state->get(['dropzonejs', $this->getFormId(), 'files']);

    if (!$files) {
      $files = [];
    }

    // We do some casting because $form_state->getValue() might return NULL.
    foreach ((array) $form_state->getValue(['upload', 'uploaded_files'], []) as $file) {
      if (file_exists($file['path'])) {
        $entity = $this->dropzoneJsUploadSave->createFile(
          $file['path'],
          $item->getUploadLocation(),
          $settings['file_extensions'],
          $this->currentUser(),
          $additional_validators
        );
        if ($entity) {
          $files[] = $entity;
        }
      }
    }

    if ($form['container']['upload']['#max_files']) {
      $files = array_slice($files, -$form['container']['upload']['#max_files']);
    }

    $form_state->set(['dropzonejs', $this->getFormId(), 'files'], $files);

    return $files;
  }

}
