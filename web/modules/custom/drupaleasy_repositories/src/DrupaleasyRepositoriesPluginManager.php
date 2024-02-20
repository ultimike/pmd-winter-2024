<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\drupaleasy_repositories\Annotation\DrupaleasyRepositories;

/**
 * DrupaleasyRepositories plugin manager.
 */
final class DrupaleasyRepositoriesPluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/DrupaleasyRepositories', $namespaces, $module_handler, DrupaleasyRepositoriesInterface::class, DrupaleasyRepositories::class);
    $this->alterInfo('drupaleasy_repositories_info');
    $this->setCacheBackend($cache_backend, 'drupaleasy_repositories_plugins');
  }

}
