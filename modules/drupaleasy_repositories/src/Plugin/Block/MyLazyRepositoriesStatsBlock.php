<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a my lazy repositories stats block.
 *
 * @Block(
 *   id = "drupaleasy_repositories_my_lazy_repositories_stats",
 *   admin_label = @Translation("My lazy repositories stats"),
 *   category = @Translation("DrupalEasy"),
 * )
 */
final class MyLazyRepositoriesStatsBlock extends BlockBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  /**
   * Constructs the plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal core entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Drupal core current user service.
   * @param \Drupal\Component\Datetime\TimeInterface $datetimeTime
   *   Drupal core datetime time service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
    private readonly TimeInterface $datetimeTime,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['content'] = [
      '#lazy_builder' => [
        static::class . '::lazyBuilder',
        [],
      ],
    ];
    return $build;
  }


  /**
   * Returns array of trusted callback methods.
   *
   * @return string[]
   *   Array of trusted callback methods.
   */
  public static function trustedCallbacks() {
    return [
      'lazyBuilder',
    ];
  }

  /**
   * {@inheritDoc}
   */
  // Public function getCacheMaxAge() {
  //   // Return Cache::PERMANENT;.
  //   return 10;
  // }.

  /**
   * {@inheritDoc}
   */
  // Public function getCacheTags() {
  //   return ['node_list:repository', 'drupaleasy_repositories'];
  // }.

  /**
   * {@inheritDoc}
   */
  public function getCacheContexts() {
    return ['timezone', 'user'];
  }

  /**
   * Calculates the total number of issues for a user's repositories.
   *
   * @param int|null $uid
   *   An (optional) user to filter on.
   *
   * @return int
   *   The total number of issues.
   */
  protected function calculateTotalIssues(int $uid = NULL): int {
    $return = 0;
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery();
    $query->condition('type', 'repository')
      ->condition('status', 1)
      ->condition('field_number_of_issues', 0, '>');
    if (!is_null($uid)) {
      $query->condition('uid', $uid);
    }
    $results = $query->accessCheck(FALSE)->execute();

    foreach ($results as $nid) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = $node_storage->load($nid);
      if ($number_of_issues = $node->field_number_of_issues->value) {
        $return += $number_of_issues;
      }
    }

    return $return;
  }

}
