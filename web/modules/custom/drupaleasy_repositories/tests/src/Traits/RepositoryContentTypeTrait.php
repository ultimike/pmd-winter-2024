<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * Provides a helper method for creating a repository content type with fields.
 */
trait RepositoryContentTypeTrait {

  /**
   * Create a Repository content type.
   */
  protected function createRepositoryContentType(): void {
    NodeType::create(['type' => 'repository', 'name' => 'Repository'])->save();

    // Create Description field.
    FieldStorageConfig::create([
      'field_name' => 'field_description',
      'type' => 'text_long',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_description',
      'entity_type' => 'node',
      'bundle' => 'repository',
      'label' => 'Description',
    ])->save();
  }

}
