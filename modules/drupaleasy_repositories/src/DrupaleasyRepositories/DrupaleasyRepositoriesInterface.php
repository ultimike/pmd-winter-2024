<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\DrupaleasyRepositories;

/**
 * Interface for drupaleasy_repositories plugins.
 */
interface DrupaleasyRepositoriesInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The plugin label from its annotation.
   */
  public function label(): string;

  /**
   * URL validator.
   *
   * @param string $uri
   *   The URI to validate.
   *
   * @return bool
   *   Returns true if valid.
   */
  public function validate(string $uri): bool;

  /**
   * Returns help text for the plugin's URL pattern requirement.
   *
   * @return string
   *   The help text.
   */
  public function validateHelpText(): string;

  /**
   * Queries the repository source for into about a repository.
   *
   * @param string $uri
   *   The URI of the repository.
   *
   * @return array<string, array<string, string|int>>
   *   The metadata about the repository.
   */
  public function getRepo(string $uri): array;

}
