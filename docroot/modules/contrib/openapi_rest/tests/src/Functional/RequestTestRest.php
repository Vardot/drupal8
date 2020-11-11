<?php

namespace Drupal\Tests\openapi_rest\Functional;

use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;
use Drupal\Tests\openapi\Functional\RequestTestBase;

/**
 * REST tests for requests on OpenAPI routes.
 *
 * @group openapi_rest
 */
final class RequestTestRest extends RequestTestBase {

  /**
   * The API module being tested.
   */
  const API_MODULE = 'rest';

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
    'hal',
    'schemata',
    'schemata_json_schema',
    'openapi',
    'rest',
    'openapi_test',
  ];

  /**
   * Tests OpenAPI requests.
   *
   * @dataProvider providerRequestTypes
   */
  public function testRequests($api_module, $options = []) {
    // Enable all the entity types each request to make sure $options is
    // respected for all parts of the spec.
    $enable_entity_types = [
      'openapi_test_entity' => ['GET', 'POST', 'PATCH', 'DELETE'],
      'openapi_test_entity_type' => ['GET'],
      'user' => ['GET'],
      'taxonomy_term' => ['GET', 'POST', 'PATCH', 'DELETE'],
      'taxonomy_vocabulary' => ['GET'],
    ];
    foreach ($enable_entity_types as $entity_type_id => $methods) {
      foreach ($methods as $method) {
        $this->enableRestService("entity:$entity_type_id", $method, 'json');
        if ($entity_type_id === 'openapi_test_entity') {
          $this->enableRestService("entity:$entity_type_id", $method, 'hal_json');
        }
      }
    }
    $this->container->get('router.builder')->rebuild();
    $this->requestOpenApiJson($api_module, $options);
  }

  /**
   * Enables the REST service interface for a specific entity type.
   *
   * @param string|false $resource_type
   *   The resource type that should get REST API enabled or FALSE to disable
   *   all resource types.
   * @param string $method
   *   The HTTP method to enable, e.g. GET, POST etc.
   * @param string|array $format
   *   (Optional) The serialization format, e.g. hal_json, or a list of formats.
   * @param array $auth
   *   (Optional) The list of valid authentication methods.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function enableRestService($resource_type, $method = 'GET', $format = 'json', array $auth = ['csrf_token']) {
    if ($resource_type) {
      // Enable REST API for this entity type.
      $resource_config_id = str_replace(':', '.', $resource_type);
      // Get entity by id.
      /** @var \Drupal\rest\RestResourceConfigInterface $resource_config */
      $resource_config = RestResourceConfig::load($resource_config_id);
      if (!$resource_config) {
        $resource_config = RestResourceConfig::create([
          'id' => $resource_config_id,
          'granularity' => RestResourceConfigInterface::METHOD_GRANULARITY,
          'configuration' => [],
        ]);
      }
      $configuration = $resource_config->get('configuration');

      if (is_array($format)) {
        for ($i = 0; $i < count($format); $i++) {
          $configuration[$method]['supported_formats'][] = $format[$i];
        }
      }
      else {

        $configuration[$method]['supported_formats'][] = $format;
      }

      foreach ($auth as $auth_provider) {
        $configuration[$method]['supported_auth'][] = $auth_provider;
      }

      $resource_config->set('configuration', $configuration);
      $resource_config->save();
    }
    else {
      foreach (RestResourceConfig::loadMultiple() as $resource_config) {
        $resource_config->delete();
      }
    }
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
    $rest_mimetypes = ['application/json'];
    if (isset($options['entity_type_id']) && $options['entity_type_id'] === 'openapi_test_entity') {
      $rest_mimetypes[] = 'application/hal+json';
    }
    $this->assertEquals($rest_mimetypes, $actual, "REST root should only contain " . implode(' and ', $rest_mimetypes));
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
