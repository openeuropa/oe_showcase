<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase_search\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\Core\Serialization\Yaml;

/**
 * Ensure Search API server and indexes are available once module is enabled.
 *
 * @group oe_showcase_search
 */
class ShowcaseSearchAssets extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'node',
    'system',
    'search_api',
    'search_api_db',
    'oe_showcase_search',
    'oe_showcase_default_content',
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'oe_showcase';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The test index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);
  }

  /**
   * Tests whether the default search was correctly installed.
   */
  public function testInstallAndDefaultSetupWorking(): void {
    $this->drupalLogin($this->adminUser);

    // Assure the Showcase server is can be used.
    $server = Server::load('showcase_search_server');
    $this->assertInstanceOf(Server::class, $server, 'Server can be loaded');

    // Assure the Showcase index can be used.
    $index = Index::load('showcase_search_index');
    $this->assertInstanceOf(Index::class, $index, 'Index can be loaded');
  }

  /**
   * Tests that all fields are added to the index, as expected.
   */
  public function testFields(): void {
    // Load the index defined in the config.
    $index = Index::load('showcase_search_index');
    $fields = $index->getFields();

    // Load and parse the same configuration file.
    $yaml_file = __DIR__ . '/../../../config/install/search_api.index.showcase_search_index.yml';
    $index_configuration = Yaml::decode(file_get_contents($yaml_file));
    $field_settings = $index_configuration['field_settings'];

    // Check that all the fields defined in the config file made it into the
    // index.
    $this->assertEquals(array_keys($fields), array_keys($field_settings));

    // Make sure that the fields have the same type.
    foreach ($field_settings as $setting) {
      $this->assertArrayHasKey($setting['property_path'], $fields);
      $field = $fields[$setting['property_path']];
      $this->assertEquals($setting['label'], $field->getLabel());
      $this->assertEquals($setting['datasource_id'], $field->getDatasourceId());
      $this->assertEquals($setting['type'], $field->getType());
    }
  }

  /**
   * Tests available indexed items.
   */
  public function testIndexedContent(): void {
    // Set index.
    $this->index = Index::load('showcase_search_index');
    $indexed_items = $this->index->indexItems();

    // Query current items.
    $query = \Drupal::entityQuery('node')->condition('type', 'oe_showcase_search_demo');
    $entity_ids = $query->execute();

    // Confirm the items are indexed.
    $this->assertCount($indexed_items, $entity_ids);
  }

}
