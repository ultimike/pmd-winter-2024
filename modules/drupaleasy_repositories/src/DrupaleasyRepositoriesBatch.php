<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Custom service class for Batch-related functionality.
 */
final class DrupaleasyRepositoriesBatch {
  use StringTranslationTrait;

  /**
   * The constructor.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $drupaleasyRepositoriesService
   *   The DrupalEasy repositories main service class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal core's entity type manager service class.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionListModule
   *   Drupal core's Module Extension list service class.
   */
  public function __construct(
    private readonly DrupaleasyRepositoriesService $drupaleasyRepositoriesService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ModuleExtensionList $extensionListModule,
  ) {}

  /**
   * Updates all user repository nodes using Batch API.
   */
  public function updateAllRepositories(): void {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $query = $user_storage->getQuery();
    $query->condition('status', 1);
    // Add condition to only include users with data in field_repository_url.
    $query->condition('field_repository_url', 0, 'IS NOT NULL');
    $users = $query->accessCheck(FALSE)->execute();
    $operations = [];
    foreach ($users as $uid => $user) {
      $operations[] = ['drupaleasy_update_repositories_batch_operation', [$uid]];
    }

    $batch = [
      'operations' => $operations,
      'finished' => 'drupaleasy_update_all_repositories_finished',
      'file' => $this->extensionListModule->getPath('drupaleasy_repositories') . '/drupaleasy_repositories.batch.inc',
    ];

    batch_set($batch);
  }

  /**
   * Batch operation callback to update user repositories.
   *
   * @param int $uid
   *   The user ID.
   * @param array<mixed>|\ArrayAccess<string, array<mixed>> $context
   *   The Batch API context.
   */
  public function updateRepositoriesBatch(int $uid, array|\ArrayAccess &$context): void {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $account = $user_storage->load($uid);
    $this->drupaleasyRepositoriesService->updateRepositories($account);

    $context['results'][] = $uid;
    $context['results']['num']++;
    $context['message'] = $this->t('Updated repositories belonging to @username.', ['@username' => $account->label()]);
  }

}
