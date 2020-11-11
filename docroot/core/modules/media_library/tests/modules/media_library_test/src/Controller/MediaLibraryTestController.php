<?php

namespace Drupal\media_library_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Returns responses for Media Library Test routes.
 */
class MediaLibraryTestController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $node_types = [
      'blog',
      'basic_page',
    ];
    foreach ($node_types as $type) {
      $build['links'][] = [
        '#attached' => [
          'library' => ['core/drupal.ajax'],
        ],
        '#type' => 'link',
        '#title' => $type,
        '#url' => Url::fromRoute('node.add', ['node_type' => $type], [
          'attributes' => [
            'class' => [
              'use-ajax',
              'modal-' . $type,
            ],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode([
              'width' => '80%',
            ]),
          ],
        ]),
      ];
    }

    return $build;
  }

}
