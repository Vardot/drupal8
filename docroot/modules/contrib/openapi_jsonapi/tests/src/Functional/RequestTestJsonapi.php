<?php

namespace Drupal\Tests\openapi_jsonapi\Functional;

use Drupal\Tests\openapi\Functional\RequestTestBase;

/**
 * REST tests for requests on OpenAPI routes.
 *
 * @group openapi_jsonapi
 */
final class RequestTestJsonapi extends RequestTestBase {

  /**
   * The API module being tested.
   */
  const API_MODULE = 'jsonapi';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests OpenAPI requests.
   *
   * @dataProvider providerRequestTypes
   */
  public function testRequests($api_module, $options = []) {
    $this->requestOpenApiJson($api_module, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRouteBase() {
    return ltrim(\Drupal::getContainer()->getParameter('jsonapi.base_path'), '/');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertMimeType(array $actual, array $options = []) {
    $this->assertEquals(['application/vnd.api+json'], $actual, "JSON:API root should only contain application/vnd.api+json");
  }

  /**
   * Builds the expectations directory.
   *
   * @return string
   *   The expectations directory.
   */
  protected function buildExpectationsDirectory() {
    return sprintf('%s/expectations/%s', dirname(dirname(__DIR__)), static::API_MODULE);
  }

}
