<?php

/**
 * @file
 * Batch-related functions for DrupalEasy Repositories module.
 */

/**
 * Batch operation callback. Used to call the proper Batch class method.
 *
 * @param int $uid
 *   The user ID.
 * @param array<mixed>|\ArrayAccess<string, array<mixed>> $context
 *   The Batch API context.
 */
function drupaleasy_update_repositories_batch_operation(int $uid, array|\ArrayAccess &$context): void {
  if (empty($context['results']['num'])) {
    $context['results']['num'] = 0;
  }
  /** @var \Drupal\drupaleasy_repositories\DrupaleasyRepositoriesBatch $drupaleasy_repositories_batch */
  $drupaleasy_repositories_batch = \Drupal::service('drupaleasy_repositories.batch');
  $drupaleasy_repositories_batch->updateRepositoriesBatch($uid, $context);
}

/**
 * Batch finished callback.
 *
 * @param bool $success
 *   TRUE if all went well.
 * @param array<mixed> $results
 *   The results array.
 * @param array<mixed> $operations
 *   The batch operations array.
 */
function drupaleasy_update_all_repositories_finished(bool $success, array $results, array $operations): void {
  if ($success) {
    if (isset($results['num'])) {
      $message = \Drupal::translation()->formatPlural(
        $results['num'],
        'One user had their repositories updated.',
        t('@number users had their repositories updated.', ['@number' => $results['num']])
      );
    }
    else {
      $message = t('Zero users had their repositories updated.');
    }
  }
  else {
    $message = t('Something went terribly wrong and the batch did not finish successfully.');
  }
  \Drupal::messenger()->addMessage($message);
}
