<?php

declare(strict_types = 1);

namespace Drupal\drupaleasy_repositories\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines drupaleasy_repositories annotation object.
 *
 * @Annotation
 */
final class DrupaleasyRepositories extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public string $label;

  /**
   * The description of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public string $description;

}
