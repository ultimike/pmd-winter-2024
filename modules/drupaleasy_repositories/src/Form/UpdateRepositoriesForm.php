<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch;
use Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService;
use Drupal\queue_ui\QueueUIBatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DrupalEasy Repositories form.
 */
final class UpdateRepositoriesForm extends FormBase {

  /**
   * The constructor.
   *
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesService $drupaleasyRepositoriesService
   *   The DrupalEasy repositories main service class.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch $drupaleasyRepositoriesBatch
   *   The DrupalEasy repositories batch service class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal core's entity type manager service class.
   * @param QueueUIBatchInterface $queueUIBatch
   *   The Queue UI batch service class.
   */
  public function __construct(
    private readonly DrupaleasyRepositoriesService $drupaleasyRepositoriesService,
    private readonly DrupaleasyRepositoriesBatch $drupaleasyRepositoriesBatch,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly QueueUIBatchInterface $queueUIBatch,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): UpdateRepositoriesForm {
    return new static(
      $container->get('drupaleasy_repositories.service'),
      $container->get('drupaleasy_repositories.batch'),
      $container->get('entity_type.manager'),
      $container->get('queue_ui.batch'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupaleasy_repositories_update_repositories';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['uid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
      '#title' => $this->t('Username'),
      '#description' => $this->t('Leave blank to update all repository nodes for all users.'),
      '#required' => FALSE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($uid = $form_state->getValue('uid')) {
      // Update all repository nodes for the selected user.
      $user_storage = $this->entityTypeManager->getStorage('user');
      $account = $user_storage->load($uid);
      if ($account) {
        if ($this->drupaleasyRepositoriesService->updateRepositories($account)) {
          $this->messenger()->addMessage($this->t('Repositories updated.'));
        }
      }
    }
    else {
      // Update all repository nodes for all users via Batch API.
      //$this->drupaleasyRepositoriesBatch->updateAllRepositories();

      // Update all repository nodes for all users via Queue API and Queue UI.
      $this->drupaleasyRepositoriesService->createQueueItems();
      // Call Queue UI (Queue Manager) to process of queue items.
      $this->queueUIBatch->batch(['drupaleasy_repositories_repository_node_updater']);
    }
  }

}
