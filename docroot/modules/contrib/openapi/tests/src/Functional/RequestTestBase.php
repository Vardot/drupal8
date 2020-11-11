<?php

namespace Drupal\Tests\openapi\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\openapi_test\Entity\OpenApiTestEntityType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Base tests for requests on OpenAPI routes.
 */
abstract class RequestTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Set to TRUE to run this test to generate expectation files.
   *
   * The test will be marked as a fail when generating test files.
   */
  protected static $generateExpectationFiles = FALSE;

  /**
   * List of required array keys for response schema.
   */
  const EXPECTED_STRUCTURE = [
    'swagger' => 'swagger',
    'info' => [
      'description' => 'description',
      'version' => 'version',
      'title' => 'title',
    ],
    'paths' => 'paths',
  ];

  protected static $entityTestBundles = [
    "taxonomy_term" => [
      "camelids",
      "taxonomy_term_test",
    ],
    "openapi_test_entity" => [
      "camelids",
      "openapi_test_entity_test",
    ],
    "openapi_test_entity_type" => [],
    "user" => [],
  ];

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /*
     * @TODO: The below configuration/setup should be shipped as part of the
     * test resources sub module.
     */
    foreach (static::$entityTestBundles['taxonomy_term'] as $bundle) {
      if (!Vocabulary::load($bundle)) {
        // Create a new vocabulary.
        $vocabulary = Vocabulary::create([
          'name' => $bundle,
          'vid' => $bundle,
        ]);
        $vocabulary->save();
      }
    }
    foreach (static::$entityTestBundles['openapi_test_entity'] as $bundle) {
      if (!OpenApiTestEntityType::load($bundle)) {
        // Create a new bundle.
        OpenApiTestEntityType::create([
          'label' => $bundle,
          'id' => $bundle,
        ])->save();
      }
    }

    foreach (array_filter(static::$entityTestBundles) as $entity_type => $bundles) {
      // Add single value and multi value fields.
      FieldStorageConfig::create([
        'entity_type' => $entity_type,
        'field_name' => 'field_test_' . $entity_type,
        'type' => 'text',
      ])
        ->setCardinality(1)
        ->save();
      foreach ($bundles as $bundle) {
        // Add field to each bundle.
        FieldConfig::create([
          'entity_type' => $entity_type,
          'field_name' => 'field_test_' . $entity_type,
          'bundle' => $bundle,
        ])
          ->setLabel('Test field')
          ->setTranslatable(FALSE)
          ->save();
      }
    }

    $this->drupalLogin($this->drupalCreateUser([
      'access openapi api docs',
      'access content',
    ]));
  }

  /**
   * Assert that test expectation generation is disabled.
   */
  public function testNotGenerating() {
    $this->assertFalse(static::$generateExpectationFiles, 'Expectation files generated. Change \Drupal\Tests\openapi\Functional\RequestTest::$generateExpectationFiles to FALSE to run tests.');
  }

  /**
   * Dataprovider for testRequests.
   */
  public function providerRequestTypes() {
    $data = [];
    foreach (static::$entityTestBundles as $entity_type => $bundles) {
      foreach ($bundles as $bundle) {
        $data[static::API_MODULE . ':' . $entity_type . '_' . $bundle] = [
          static::API_MODULE,
          [
            'entity_type_id' => $entity_type,
            'bundle_name' => $bundle,
          ],
        ];
      }
      // Test all bundles for entity type.
      $data[static::API_MODULE . ':' . $entity_type] = [
        static::API_MODULE,
        [
          'entity_type_id' => $entity_type,
        ],
      ];
    }
    // Test all entity types and bundle for module.
    $data[static::API_MODULE] = [static::API_MODULE];
    return $data;
  }

  /**
   * Makes OpenAPI request and checks the response.
   *
   * @param string $api_module
   *   The API module being tested.
   * @param array $options
   *   The query options for generating the OpenAPI output.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function requestOpenApiJson($api_module, array $options = []) {
    $get_options = [
      'query' => [
        '_format' => 'json',
        'options' => $options,
      ],
    ];
    $response = $this->drupalGet("openapi/$api_module", $get_options);
    $decoded_response = json_decode($response, TRUE);
    $this->assertSession()->statusCodeEquals(200);

    // Test the the first tier schema has the expected keys.
    $structure_keys = array_keys(static::EXPECTED_STRUCTURE);
    $response_keys = array_keys($decoded_response);
    $missing = array_diff($structure_keys, $response_keys);
    $this->assertTrue(empty($missing), 'Schema missing expected key(s): ' . implode(', ', $missing));

    // Test that the required info block keys exist in the response.
    $structure_info_keys = array_keys(static::EXPECTED_STRUCTURE['info']);
    $response_keys = array_keys($decoded_response['info']);
    $missing_info = array_diff($structure_info_keys, $response_keys);
    $this->assertTrue(empty($missing_info), 'Schema info missing expected key(s): ' . implode(', ', $missing_info));

    // Test that schemes is not empty.
    $this->assertTrue(!empty($decoded_response['schemes']), 'Schema for ' . $api_module . ' should define at least one scheme.');

    // Test basePath and host.
    $port = parse_url($this->baseUrl, PHP_URL_PORT);
    $host = parse_url($this->baseUrl, PHP_URL_HOST) . ($port ? ':' . $port : '');
    $this->assertEquals($host, $decoded_response['host'], 'Schema has invalid host.');
    $basePath = $this->getBasePath();
    $response_basePath = $decoded_response['basePath'];
    $this->assertEquals($basePath, substr($response_basePath, 0, strlen($basePath)), 'Schema has invalid basePath.');
    $response_routeBase = substr($response_basePath, strlen($basePath));
    // Verify that with the subdirectory removed, that the basePath is correct.
    $this->assertEquals($this->getRouteBase(), ltrim($response_routeBase, '/'), 'Route base path is invalid.');

    // Verify that root consumes and produces exists and is not empty.
    foreach (['consumes', 'produces'] as $key) {
      $this->assertArrayHasKey($key, $decoded_response, "Schema does not contains a root $key");
      $this->assertNotEmpty($decoded_response[$key], "Schema has empty root $key");
      if (!isset($decoded_response[$key])) {
        $this->assertMimeType($decoded_response[$key], $options);
      }
    }

    /*
     * Tags for rest schema define 'x-entity-type' to reference the entity type
     * associated with the entity. This value should exist in the definitions.
     *
     * NOTE: Currently not all entity types are provided as definitions. As a
     * result, the below test is subject to failure, and has been disabled.
     *
     * @TODO: #2940397 - Convert x-entity-type to x-definition.
     * @TODO: #2940407 - Provide all entity types as definitions.
     */
    $tags = $decoded_response['tags'];
    if (FALSE) {
      $definitions = $decoded_response['definitions'];
      foreach ($tags as $tag) {
        if (isset($tag['x-entity-type'])) {
          $type_id = $tag['x-entity-type'];
          $this->assertTrue(isset($definitions[$type_id]), 'The \'x-entity-type\' ' . $type_id . ' is invalid for ' . $tag['name'] . '.');
        }
      }
    }

    // Validate that all security definitions are valid, and have a provider.
    $security_definitions = $decoded_response['securityDefinitions'];
    $auth_providers = $this->container->get('authentication_collector')->getSortedProviders();
    $supported_security_types = ['basic', 'apiKey', 'cookie', 'oauth', 'oauth2'];
    foreach ($security_definitions as $definition_id => $definition) {
      if ($definition_id !== 'csrf_token') {
        // CSRF Token will never have an auth collector, all others shoud.
        $this->assertTrue(array_key_exists($definition_id, $auth_providers), 'Security definition ' . $definition_id . ' not an auth collector.');
      }
      $this->assertTrue(in_array($definition['type'], $supported_security_types), 'Security definition schema ' . $definition_id . ' has invalid type '. $definition['type']);
    }

    // Test paths for valid tags, schema, security, and definitions.
    $paths = &$decoded_response['paths'];
    $tag_names = array_column($tags, 'name');
    $all_method_tags = [];
    foreach ($paths as $path => &$methods) {
      foreach ($methods as $method => &$method_schema) {
        // Ensure all tags are defined.
        $missing_tags = array_diff($method_schema['tags'], $tag_names);
        $all_method_tags = array_merge($all_method_tags, $method_schema['tags']);
        $this->assertTrue(empty($missing_tags), 'Method ' . $method . ' for ' . $path . ' has invalid tag(s): ' . implode(', ', $missing_tags));

        // Ensure all request schemes are defined.
        if (isset($method_schema['schemes'])) {
          $missing_schemas = array_diff($method_schema['schemes'], $decoded_response['schemes']);
          $this->assertTrue(empty($missing_schemas), 'Method ' . $method . ' for ' . $path . ' has invalid scheme(s): ' . implode(', ', $missing_schemas));
        }

        $response_security_types = array_keys($decoded_response['securityDefinitions']);
        if (isset($method_schema['security'])) {
          foreach ($method_schema['security'] as $security_definitions) {
            $security_types = array_keys($security_definitions);
            $missing_security_types = array_diff($security_types, $response_security_types);
            $this->assertTrue(empty($missing_security_types), 'Method ' . $method . ' for ' . $path . ' has invalid security type(s): ' . implode(', ', $missing_security_types) . ' + ' . implode(', ', $security_types) . ' + ' . implode(', ', $response_security_types));
          };
        }

        foreach (['consumes', 'produces'] as $key) {
          if (isset($method_schema[$key]) && !empty($method_schema[$key])) {
            // Filter out mimetypes that exist in parent.
            $method_extra_mimetypes = array_diff($method_schema[$key], $decoded_response[$key]);
            $this->assertEmpty($method_extra_mimetypes, 'Method ' . $method . ' for ' . $path . ' has invalid mime type(s): ' . implode(', ', $method_extra_mimetypes));

            if ($api_module == 'rest') {
              $rest_mimetypes = ['application/json'];
              if (isset($options['entity_type_id']) && $options['entity_type_id'] === 'openapi_test_entity') {
                $rest_mimetypes[] = 'application/hal+json';
              }
              $this->assertEquals($rest_mimetypes, $method_schema[$key], 'Entity type ' . $options['entity_type_id'] . ' should only have REST mimetype(s): ' . implode(', ', $rest_mimetypes));
            }
          }
        }

        // Remove all tested properties from method schema.
        unset($method_schema['tags']);
        unset($method_schema['schemes']);
        unset($method_schema['security']);
      }
    }
    $all_method_tags = array_unique($all_method_tags);
    asort($all_method_tags);
    asort($tag_names);
    $this->assertEquals(array_values($all_method_tags), array_values($tag_names), "Method tags equal tag names");

    // Strip response down to only untested properties.
    $root_keys = ['definitions', 'paths'];
    foreach (array_diff(array_keys($decoded_response), $root_keys) as $remove) {
      unset($decoded_response[$remove]);
    }

    // Build file name.
    $file_name = $this->buildExpectationsDirectory();
    if ($options) {
      $file_name .= "." . implode('.', $options);
    }
    $file_name .= '.json';
    if (static::$generateExpectationFiles) {
      $this->saveExpectationFile($file_name, $decoded_response);
      // Response assertion is not performed when generating expectation
      // files.
      return;
    }
    // Load expected value and test remaining schema.
    $expected = json_decode(file_get_contents($file_name), TRUE);

    $this->nestedKsort($expected);
    $this->nestedKsort($decoded_response);
    $this->assertEquals($expected, $decoded_response, "The response does not match expected file: $file_name");
  }

  /**
   * Builds the expectations directory.
   *
   * @return string
   *   The expectations directory.
   */
  abstract protected function buildExpectationsDirectory();

  /**
   * Saves an expectation file.
   *
   * @param string $file_name
   *   The file name of the expectation file.
   * @param array $decoded_response
   *   The decoded JSON response.
   *
   * @see \Drupal\Tests\openapi\Functional\RequestTest::GENERATE_EXPECTATION_FILES
   */
  private function saveExpectationFile($file_name, array $decoded_response) {
    // Remove the base path from the start of the string, if present.
    $re_encode = json_encode($decoded_response, JSON_PRETTY_PRINT);
    file_put_contents($file_name, $re_encode);
  }

  /**
   * Gets the route base.
   *
   * @return string
   *   The route base.
   */
  abstract protected function getRouteBase();

  /**
   * Assert the correct MIME types.
   *
   * @param string[] $actual
   *   The actual MIME types.
   * @param array $options
   *   Additional options.
   */
  abstract protected function assertMimeType(array $actual, array $options = []);

  /**
   * Gets the base path to be used in OpenAPI.
   *
   * @return string
   *   The base path.
   */
  private function getBasePath() {
    $path = rtrim(parse_url($this->baseUrl, PHP_URL_PATH), '/');

    // OpenAPI spec states that the base path must start with a '/'.
    if (strlen($path) == 0) {
      // For a zero length string, set it to minimal value.
      $path = "/";
    }
    elseif (substr($path, 0, 1) !== '/') {
      // Prepend a slash to any other string that don't have one.
      $path = '/' . $path;
    }
    return $path;
  }

  /**
   * Sorts a nested array with ksort().
   *
   * @param array $array
   *   The nested array to sort.
   */
  private function nestedKsort(array &$array) {
    if ($this->isAssocArray($array)) {
      ksort($array);
    }
    else {
      usort($array, function ($a, $b) {
        if (isset($a['name']) && isset($b['name'])) {
          return strcmp($a['name'], $b['name']);
        }
        return -1;
      });
    }

    foreach ($array as &$item) {
      if (is_array($item)) {
        $this->nestedKsort($item);
      }
    }
  }

  /**
   * Determine if an array is associative array.
   *
   * @param array $arr
   *   The array.
   *
   * @return bool
   *   TRUE if the array is associative, otherwise false.
   */
  private function isAssocArray(array $arr) {
    if (empty($arr)) {
      return FALSE;
    }
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

}
