<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories;

/**
 * Interface for drupaleasy_repositories plugins.
 */
interface DrupaleasyRepositoriesInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

}
