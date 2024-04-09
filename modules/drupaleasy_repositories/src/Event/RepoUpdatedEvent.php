<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * Event that is fired when a repository is updated, created, or deleted.
 */
class RepoUpdatedEvent extends Event {

  /**
   * The name of the event triggered when a repository is updated.
   *
   * @Event
   *
   * @var string
   */
  const REPO_UPDATED = 'drupaleasy_repositories_repo_updated';

  /**
   * Constructs a new RepoUpdatedEvent object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object that was updated/deleted/created.
   * @param string $action
   *   The action performed on the node.
   */
  public function __construct(
    public NodeInterface $node,
    public string $action
  ) {}

}
