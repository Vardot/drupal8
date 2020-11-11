<?php

namespace Drupal\Tests\openapi_jsonapi\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\openapi_test\Entity\OpenApiTestEntityType;

/**
 * @coversDefaultClass \Drupal\openapi_jsonapi\Plugin\openapi\OpenApiGenerator\JsonApiGenerator
 *
 * @group openapi_jsonapi
 */
final class JsonApiGeneratorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'link',
    'menu_link_content',
    'filter',
    'text',
    'taxonomy',
    'serialization',
    'jsonapi',
    'schemata',
    'schemata_json_schema',
    'openapi',
    'openapi_test',
    'openapi_jsonapi',
    'system',
    'user',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('openapi_test_entity');

    OpenApiTestEntityType::create([
      'id' => 'test',
      'label' => 'Test',
    ])->save();
  }

  /**
   * @covers ::getPaths
   */
  public function testGetPaths() {
    // Assert that the menu_link field is defined on the test entity type.
    $field_definitions = $this->container
      ->get('entity_field.manager')
      ->getFieldDefinitions('openapi_test_entity', 'test');

    $this->assertArrayHasKey('menu_link', $field_definitions);

    // This should not cause any failures.
    $this->container->get('plugin.manager.openapi.generator')
      ->createInstance('jsonapi')
      ->getPaths();
  }

}
