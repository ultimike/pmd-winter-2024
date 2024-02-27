<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

/**
 * Custom functionality for Drupaleasy repositories plugin stuff.
 */
final class DrupaleasyRepositoriesService {

  /**
   * Returns validator help text for enabled plugins of our type.
   *
   * @return string
   *   The help text.
   */
  public function getValidatorHelpText(): string {
    // Determine which plugins of our type are enabled (config).

    // Instantiate all of the enabled plugins (plugin manager).

    // Loop around enabled plugins and call their validateHelpText() methods.

    // Concatenate results and return.

    return '';
  }

}
