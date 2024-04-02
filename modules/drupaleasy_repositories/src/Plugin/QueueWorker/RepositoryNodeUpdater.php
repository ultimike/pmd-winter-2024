<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'drupaleasy_repositories_repository_node_updater' queue worker.
 *
 * @QueueWorker(
 *   id = "drupaleasy_repositories_repository_node_updater",
 *   title = @Translation("Repository node updater"),
 *   cron = {"time" = 60},
 * )
 */
final class RepositoryNodeUpdater extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Our custom service class.
   *
   * @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService
   */
  protected DrupaleasyRepositoriesService $repositoriesService;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $repositories_service
   *   Our custom repositories service class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal core entity type manager service.
   */
  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, DrupaleasyRepositoriesService $repositories_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->repositoriesService = $repositories_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, mixed $plugin_id, mixed $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('drupaleasy_repositories.service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (isset($data['uid'])) {
      $user = $this->entityTypeManager->getStorage('user')->load($data['uid']);
      // Check to make the user exists - in case they have been deleted since'
      // the queue item was created.
      if (isset($user)) {
        $this->repositoriesService->updateRepositories($user);
      }
    }
  }

}
