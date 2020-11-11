<?php

namespace Drupal\openapi_test\Plugin\openapi\OpenApiGenerator;

use Drupal\openapi\Plugin\openapi\OpenApiGeneratorBase;

/**
 * The test generator.
 *
 * @OpenApiGenerator(
 *   id = "null",
 *   label = @Translation("Null"),
 * )
 */
final class NullGenerator extends OpenApiGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function getApiName() {
    return 'null';
  }

  /**
   * {@inheritdoc}
   */
  protected function getJsonSchema($described_format, $entity_type_id, $bundle_name = NULL) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getApiDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumes() {
    return ['null'];
  }

  /**
   * {@inheritdoc}
   */
  public function getProduces() {
    return ['null'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTags() {
    return ['name' => 'null', 'description' => $this->t('NULL')];
  }

}
