<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Stops DrupalEasy Repositories module from being uninstalled when data exists.
 *
 * These conditions are when any data exists in the field_repository_url on User
 * entities.
 */
class DrupaleasyRepositoriesUninstallValidator implements ModuleUninstallValidatorInterface {
  use StringTranslationTrait;

  /**
   * Constructs a new DrupaleasyRepositoriesUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function validate($module): array {
    $reasons = [];
    if ($module === 'drupaleasy_repositories') {
      if ($this->hasFieldRepositoryUrlData()) {
        $reasons[] = $this->t('To uninstall the DrupalEasy Repositories module, delete all data from the "Repository URLs" field on User entities.')->render();
      }
    }
    return $reasons;
  }

  /**
   * Determines if there is any data in field_repository_url or not.
   *
   * @return bool
   *   TRUE if there is data, FALSE otherwise.
   */
  protected function hasFieldRepositoryUrlData() {
    $users = $this->entityTypeManager->getStorage('user')->getQuery()
      ->condition('field_repository_url', 0, 'IS NOT NULL')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($users);
  }

}
