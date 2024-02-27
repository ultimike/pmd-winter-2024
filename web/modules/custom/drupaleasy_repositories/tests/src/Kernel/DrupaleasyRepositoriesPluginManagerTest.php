<?php

declare(strict_types=1);

namespace Drupal\Tests\drupaleasy_repositories\Kernel;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class DrupaleasyRepositoriesPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<int, string>
   */
  protected static $modules = ['drupaleasy_repositories'];

  /**
   * Our plugin manager.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager
   */
  protected DrupaleasyRepositoriesPluginManager $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.drupaleasy_repositories');
  }

  /**
   * Test creating an instance of the .yml remtoe plugin.
   *
   * @covers DrupaleasyRepositoriesPluginManager::class
   * @test
   */
  public function testYmlRemoteInstance(): void {
    /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase $example_instance */
    $example_instance = $this->manager->createInstance('yml_remote');
    $plugin_def = $example_instance->getPluginDefinition();

    $this->assertInstanceOf('Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase', $example_instance, 'Plugin parent class is not the proper type.');
    $this->assertInstanceOf('Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote', $example_instance, 'Plugin is not the proper type.');
    $this->assertArrayHasKey('label', $plugin_def, 'Label array key not found.');
    $this->assertTrue($plugin_def['label'] == 'Yml remote', 'The label value is not correct.');
  }

}
