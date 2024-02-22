<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\DrupaleasyRepositories;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for drupaleasy_repositories plugins.
 */
abstract class DrupaleasyRepositoriesPluginBase extends PluginBase implements DrupaleasyRepositoriesInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    return FALSE;
  }

  /**
   * Build array of a single repository.
   *
   * @param string $machine_name
   *   The machine name of the repository.
   * @param string $label
   *   The user-facing name of the repository.
   * @param string|null $description
   *   The description of the repository.
   * @param int $num_open_issues
   *   The number of open issues that the repository has.
   * @param string $url
   *   The URI of the repository.
   *
   * @return array<string, array<string, string|int>>
   *   A consistent array of repository metadata.
   */
  protected function mapToCommonFormat(string $machine_name, string $label, string|null $description, int $num_open_issues, string $url): array {
    $repo_info[$machine_name] = [
      'label' => $label,
      'description' => $description,
      'num_open_issues' => $num_open_issues,
      'source' => $this->getPluginId(),
      'url' => $url,
    ];
    return $repo_info;
  }

}
