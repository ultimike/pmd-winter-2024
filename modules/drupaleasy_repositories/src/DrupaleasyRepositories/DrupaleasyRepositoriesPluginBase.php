<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\DrupaleasyRepositories;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\key\KeyRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for drupaleasy_repositories plugins.
 */
abstract class DrupaleasyRepositoriesPluginBase extends PluginBase implements DrupaleasyRepositoriesInterface, ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * The repository client object.
   *
   * @var Object
   */
  protected Object $client;

  /**
   * The Drupal core messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The PRS logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The Key module's service.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected KeyRepositoryInterface $keyRepository;

  /**
   * The constructor.
   *
   * @param array<mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Drupal core messenger service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The PRS Logger service.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The Key repository service.
   */
  final public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, MessengerInterface $messenger, LoggerInterface $logger, KeyRepositoryInterface $key_repository) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->keyRepository = $key_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('logger.factory')->get('drupaleasy_repositories'),
      $container->get('key.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    return FALSE;
  }

  /**
   * Build array of a single repository.
   *
   * @param string $machine_name
   *   The machine name of the repository.
   * @param string $label
   *   The user-facing name of the repository.
   * @param string|null $description
   *   The description of the repository.
   * @param int $num_open_issues
   *   The number of open issues that the repository has.
   * @param string $url
   *   The URI of the repository.
   *
   * @return array<string, array<string, string|int>>
   *   A consistent array of repository metadata.
   */
  protected function mapToCommonFormat(string $machine_name, string $label, string|null $description, int $num_open_issues, string $url): array {
    $repo_info[$machine_name] = [
      'label' => $label,
      'description' => $description,
      'num_open_issues' => $num_open_issues,
      'source' => $this->getPluginId(),
      'url' => $url,
    ];
    return $repo_info;
  }

}
