<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure DrupalEasy Repositories settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal core configuration factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The Drupal core typed configuration manager.
   * @param \Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginManager $repositoriesManager
   *   The DrupaleasyRepositories plugin manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config,
    protected DrupaleasyRepositoriesPluginManager $repositoriesManager
  ) {
    parent::__construct($config_factory, $typed_config);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('plugin.manager.drupaleasy_repositories'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drupaleasy_repositories_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['drupaleasy_repositories.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array<string, mixed>
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Use the PHP Null Coalescing Operator in case the config doesn't exist
    // yet.
    $repositories_config = $this->config('drupaleasy_repositories.settings')
      ->get('repositories_plugins') ?? [];

    $repositories = $this->repositoriesManager->getDefinitions();
    uasort($repositories, static function ($a, $b) {
      return Unicode::strcasecmp($a['label'], $b['label']);
    });
    $repositories_options = [];
    foreach ($repositories as $id => $repository) {
      $repositories_options[$id] = $repository['label'];
    }

    $form['repositories_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Repository plugins'),
      '#options' => $repositories_options,
      '#default_value' => $repositories_config,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('drupaleasy_repositories.settings')
      ->set('repositories_plugins', $form_state->getValue('repositories_plugins'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
