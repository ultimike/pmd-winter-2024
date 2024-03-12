<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Custom functionality for Drupaleasy repositories plugin stuff.
 */
final class DrupaleasyRepositoriesService {
  use StringTranslationTrait;

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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getValidatorHelpText(): string {
    // Determine which plugins of our type are enabled (config).
    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];

    // Instantiate all the enabled plugins (plugin manager).
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

  /**
   * Validates repository URLs.
   *
   * @param array<int, array<string, mixed>> $urls
   *   The repository URLs.
   * @param int $uid
   *   The user ID.
   *
   * @return string
   *   An error message, if any.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function validateRepositoryUrls(array $urls, int $uid): string {
    $errors = [];
    $repository_plugins = [];

    // Determine which plugins of our type are enabled (config).
    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];

    // Instantiate and loop around each of the enabled plugins (plugin manager).
    $atLeastOne = FALSE;
    foreach ($repository_plugin_ids as $repository_plugin_id) {
      if (!empty($repository_plugin_id)) {
        $repository_plugins[] = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);
        $atLeastOne = TRUE;
      }
    }
    if (!$atLeastOne) {
      return 'There are no enabled repository plugins.';
    }

    // Loop around each URL to validate.
    foreach ($urls as $url) {
      if (is_array($url)) {
        if ($uri = trim($url['uri'])) {
          // Loop around the enabled plugins and call their validate() methods.
          $is_valid_url = FALSE;
          /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin*/
          foreach ($repository_plugins as $repository_plugin) {
            if ($repository_plugin->validate($uri)) {
              $is_valid_url = TRUE;
              break;
            }
          }
          if (!$is_valid_url) {
            $errors[] = $this->t('The repository url %uri is not valid.', ['%uri' => $uri]);
          }
        }
      }
    }

    if ($errors) {
      return implode(' ', $errors);
    }
    return '';
  }

}
