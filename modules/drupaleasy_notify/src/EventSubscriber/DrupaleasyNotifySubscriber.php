<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_notify\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\drupaleasy_repositories\Event\RepoUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * DrupalEasy Notify event subscriber.
 */
final class DrupaleasyNotifySubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Constructs a DrupaleasyNotifySubscriber object.
   */
  public function __construct(
    private readonly MessengerInterface $messenger,
  ) {}

  /**
   * Kernel request event handler.
   */
  public function onRepoUpdated(RepoUpdatedEvent $event): void {
    $this->messenger->addStatus($this->t('The repository named %repo_name has been @action (@repo_url). The repository node is owned by @author_name (@author_id).', [
      '%repo_name' => $event->node->getTitle(),
      '@action' => $event->action,
      '@repo_url' => $event->node->toLink()->getUrl()->toString(),
      '@author_name' => $event->node->uid->entity->name->value,
      '@author_id' => $event->node->uid->target_id,
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RepoUpdatedEvent::REPO_UPDATED => ['onRepoUpdated'],
    ];
  }

}
