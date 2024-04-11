<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drupal\queue_ui\QueueUIBatchInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
final class DrupaleasyRepositoriesCommands extends DrushCommands {

  /**
   * Constructs a DrupaleasyRepositoriesCommands object.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $repositoriesService
   *   The DrupaleasyRepositoriesService service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal core entity type manager.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch $batch
   *   The DrupaleasyRepositoriesBatch service.
   * @param \Drupal\queue_ui\QueueUIBatchInterface $queueUIBatch
   *   The Queue UI batch service class.
   */
  public function __construct(
    private readonly DrupaleasyRepositoriesService $repositoriesService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly DrupaleasyRepositoriesBatch $batch,
    private readonly QueueUIBatchInterface $queueUIBatch,
  ) {
    parent::__construct();
  }

  /**
   * Update user repositories.
   *
   * This command will update all user repositories or all repositories for a
   * single user.
   *
   * @param array<string, int|null> $options
   *   An associative array of options whose values come from cli, aliases,
   *    config, etc.
   */
  #[CLI\Command(name: 'der:update-repositories', aliases: ['der:ur'])]
  #[CLI\Option(name: 'uid', description: 'The user ID to update repositories for.')]
  #[CLI\Help(description: 'Update user repositories.', synopsis: 'This command will update all user repositories or all repositories for a single user.')]
  #[CLI\Usage(name: 'der:update-repositories --uid=2', description: 'Update a user\'s repositories.')]
  #[CLI\Usage(name: 'der:update-repositories', description: 'Update all user repositories.')]
  public function updateRepositories(array $options = ['uid' => NULL]): void {
    if (!empty($options['uid'])) {
      // Update all repository nodes for the selected user.
      /** @var \Drupal\user\UserStorageInterface $user_storage */
      $user_storage = $this->entityTypeManager->getStorage('user');
      $account = $user_storage->load($options['uid']);
      if ($account) {
        if ($this->repositoriesService->updateRepositories($account)) {
          $this->logger()->notice(dt('Repositories updated.'));
        }
      }
      else {
        $this->logger()->alert(dt('User does not exist!'));
      }
    }
    else {
      if (!is_null($options['uid'])) {
        $this->logger()->alert(dt('You may not select the Anonymous user!'));
        return;
      }
      // Update all repository nodes for all users via Batch API.
      // $this->batch->updateAllRepositories(TRUE);
      // Update all repository nodes for all users via Queue API and Queue UI.
      $this->repositoriesService->createQueueItems();
      // Call Queue UI (Queue Manager) to process of queue items.
      $this->queueUIBatch->batch(['drupaleasy_repositories_repository_node_updater']);
      drush_backend_batch_process();
    }
  }

}
