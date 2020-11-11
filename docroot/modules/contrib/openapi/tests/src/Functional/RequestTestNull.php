<?php

namespace Drupal\Tests\openapi\Functional;

/**
 * REST tests for requests on OpenAPI routes.
 *
 * @group openapi
 */
final class RequestTestNull extends RequestTestBase {

  /**
   * The API module being tested.
   */
  const API_MODULE = 'null';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'field',
    'filter',
    'text',
    'taxonomy',
    'serialization',
    'openapi',
    'openapi_test',
  ];

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
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function assertMimeType(array $actual, array $options = []) {
    $this->assertEquals(['null'], $actual);
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
