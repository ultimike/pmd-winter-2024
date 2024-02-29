<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Custom functionality for Drupaleasy repositories plugin stuff.
 */
final class DrupaleasyRepositoriesService {

  /**
   * The DrupalEasy Repositories plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected PluginManagerInterface $pluginManagerDrupaleasyRepositories;

  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a DrupaleasyRepositories service object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The Drupaleasy repositories plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal core configuration factory.
   */
  public function __construct(PluginManagerInterface $plugin_manager, ConfigFactoryInterface $config_factory) {
    $this->pluginManagerDrupaleasyRepositories = $plugin_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Returns validator help text for enabled plugins of our type.
   *
   * @return string
   *   The help text.
   */
  public function getValidatorHelpText(): string {
    // Determine which plugins of our type are enabled (config).
    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];

    // Instantiate all of the enabled plugins (plugin manager).
    $repository_plugins = [];
    foreach ($repository_plugin_ids as $repository_plugin_id) {
      if (!empty($repository_plugin_id)) {
        $repository_plugins[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);
      }
    }

    // Loop around enabled plugins and call their validateHelpText() methods.
    $help = [];
    /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
    foreach ($repository_plugins as $repository_plugin) {
      $help[] = $repository_plugin->validateHelpText();
    }

    // Concatenate results and return.
    return implode(' ', $help);
  }

}
