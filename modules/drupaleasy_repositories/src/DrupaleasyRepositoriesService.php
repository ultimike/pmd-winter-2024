<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Custom functionality for Drupaleasy repositories plugin stuff.
 */
final class DrupaleasyRepositoriesService {
  use StringTranslationTrait;

  /**
   * Constructs a DrupaleasyRepositories service object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManagerDrupaleasyRepositories
   *   The Drupaleasy repositories plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Drupal core configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The Drupal core queue factory service.
   * @param bool $dryRun
   *   The dry_run parameter that specifies whether to save node changes.
   */
  public function __construct(
    protected PluginManagerInterface $pluginManagerDrupaleasyRepositories,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected QueueFactory $queue,
    protected bool $dryRun = FALSE
  ) {}

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
              $repo_metadata = $repository_plugin->getRepo($uri);
              if ($repo_metadata) {
                if (!$this->isUnique($repo_metadata, $uid)) {
                  $errors[] = $this->t('The repository at %uri has been added by another user.', ['%uri' => $uri]);
                }
              }
              else {
                $errors[] = $this->t('The repository at the url %uri was not found.', ['%uri' => $uri]);
              }
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

  /**
   * Update the repository nodes for a given account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function updateRepositories(EntityInterface $account): bool {
    $repos_metadata = [];
    $repository_plugin_ids = $this->configFactory->get('drupaleasy_repositories.settings')->get('repositories_plugins') ?? [];

    foreach ($repository_plugin_ids as $repository_plugin_id) {
      if (!empty($repository_plugin_id)) {

        /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesInterface $repository_plugin */
        $repository_plugin = $this->pluginManagerDrupaleasyRepositories->createInstance($repository_plugin_id);
        // Loop through repository URLs.
        foreach ($account->field_repository_url ?? [] as $url) {
          // Check if the URL validates for this repository.
          if ($repository_plugin->validate($url->uri)) {

            // Confirm the repository exists and get metadata.
            if ($repo_metadata = $repository_plugin->getRepo($url->uri)) {
              $repos_metadata += $repo_metadata;
            }
          }
        }
      }
    }
    $repos_updated = $this->updateRepositoryNodes($repos_metadata, $account);
    $repos_deleted = $this->deleteRepositoryNodes($repos_metadata, $account);
    return $repos_updated || $repos_deleted;
  }

  /**
   * Update repository nodes for a given user.
   *
   * @param array<string, array<string, string|int>> $repos_info
   *   Repository info from API call.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    if (!$repos_info) {
      return TRUE;
    }
    // Prepare the storage and query stuff.
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    foreach ($repos_info as $key => $repo_info) {
      // Calculate hash value.
      $hash = md5(serialize($repo_info));

      // Look for repository nodes from this user with matching
      // machine_name.
      $query = $node_storage->getQuery()->accessCheck(FALSE);
      $query->condition('type', 'repository')
        ->condition('uid', $account->id())
        ->condition('field_machine_name', $key)
        ->condition('field_source', $repo_info['source']);
      $results = $query->execute();

      if ($results) {
        /** @var \Drupal\node\Entity\Node $node */
        $node = $node_storage->load(reset($results));

        if ($hash != $node->get('field_hash')->value) {
          // Something changed, update node.
          $node->setTitle($repo_info['label']);
          $node->set('field_description', $repo_info['description']);
          $node->set('field_machine_name', $key);
          $node->set('field_number_of_issues', $repo_info['num_open_issues']);
          $node->set('field_source', $repo_info['source']);
          $node->set('field_url', $repo_info['url']);
          $node->set('field_hash', $hash);

          if (!$this->dryRun) {
            $node->save();
            // $this->repoUpdated($node, 'updated');
          }
        }
      }
      else {
        // Repository node doesn't exist - create a new one.
        /** @var \Drupal\node\NodeInterface $node */
        $node = $node_storage->create([
          'uid' => $account->id(),
          'type' => 'repository',
          'title' => $repo_info['label'],
          'field_description' => $repo_info['description'],
          'field_machine_name' => $key,
          'field_number_of_issues' => $repo_info['num_open_issues'],
          'field_source' => $repo_info['source'],
          'field_url' => $repo_info['url'],
          'field_hash' => $hash,
        ]);
        if (!$this->dryRun) {
          $node->save();
          // $this->repoUpdated($node, 'created');
        }
      }
    }
    return TRUE;
  }

  /**
   * Delete repository nodes deleted from the source for a given user.
   *
   * @param array<string, array<string, string>> $repos_info
   *   Repository info from API call.
   * @param \Drupal\Core\Entity\EntityInterface $account
   *   The user account whose repositories to update.
   *
   * @return bool
   *   TRUE if successful.
   */
  protected function deleteRepositoryNodes(array $repos_info, EntityInterface $account): bool {
    // Prepare the storage and query stuff.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('uid', $account->id())
      ->accessCheck(FALSE);
    // We can't chain this above because $repos_info might be empty.
    if ($repos_info) {
      $query->condition('field_machine_name', array_keys($repos_info), 'NOT IN');
    }
    $results = $query->execute();
    if ($results) {
      $nodes = $node_storage->loadMultiple($results);
      /** @var \Drupal\node\Entity\Node $node */
      foreach ($nodes as $node) {
        if (!$this->dryRun) {
          $node->delete();
          // $this->repoUpdated($node, 'deleted');
        }
      }
    }
    return TRUE;
  }

  /**
   * Check to see if the repository is unique.
   *
   * @param array<string, array<string, string|int>> $repo_info
   *   The repository info.
   * @param int $uid
   *   The user ID of the submitter.
   *
   * @return bool
   *   Return true if the repository is unique.
   */
  protected function isUnique(array $repo_info, int $uid): bool {
    $node_storage = $this->entityTypeManager->getStorage('node');

    $repo_metadata = array_pop($repo_info);

    // Look for repository nodes with a matching url.
    $query = $node_storage->getQuery();
    $results = $query->condition('type', 'repository')
      ->condition('field_url', $repo_metadata['url'])
      ->condition('uid', $uid, '<>')
      ->accessCheck(FALSE)
      ->execute();

    return !count($results);
  }

  /**
   * Create queue items for each user.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createQueueItems(): void {
    $query = $this->entityTypeManager->getStorage('user')->getQuery();
    $query->condition('status', 1);
    // Add condition to only include users with data in field_repository_url.
    $query->condition('field_repository_url', 0, 'IS NOT NULL');
    $users = $query->accessCheck(FALSE)->execute();

    // Create a queue item for each user.
    $queue = $this->queue->get('drupaleasy_repositories_repository_node_updater');
    foreach ($users as $uid => $user) {
      $queue->createItem(['uid' => $uid]);
    }
  }

}
