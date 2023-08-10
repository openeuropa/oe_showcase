<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_showcase\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that post updates are executed in the correct order.
 *
 * @group batch1
 */
class PostUpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'oe_showcase_updates_test',
    'z_module',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setInstallProfile('oe_showcase');

    // We need to manually enable the profile because adding it to the
    // modules variable triggers an error (since it is not a module).
    $this->enableModules(['oe_showcase']);
    \Drupal::moduleHandler()->loadInclude('oe_showcase', 'install');
  }

  /**
   * Tests that oe_showcase post-updates are executed last.
   */
  public function testPendingUpdateOrder(): void {
    /** @var \Drupal\Core\Update\UpdateRegistry $update_registry_factory */
    $update_registry = $this->container->get('update.post_update_registry');
    $update_functions = $update_registry->getPendingUpdateFunctions();
    // Check that the last method is the yet to run oe_showcase post update
    // method.
    $last_update = end($update_functions);
    $this->assertEquals('oe_showcase_updates_test_post_update_00001', $last_update);
    // Check that the post update hook in the module with the letter Z
    // is also in the list, before the first of the oe_showcase ones.
    $key = array_search('z_module_post_update_00001', $update_functions);
    $this->assertIsNumeric($key);
    $this->assertEquals('oe_showcase_post_update_00001', $update_functions[$key + 1]);
  }

}
