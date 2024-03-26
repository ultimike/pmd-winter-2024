<?php

declare(strict_types=1);

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;
use Github\AuthMethod;
use Github\Client;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "github",
 *   label = @Translation("GitHub"),
 *   description = @Translation("GitHub.com")
 * )
 */
final class Github extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validate(string $uri): bool {
    $pattern = '|^https://github.com/[a-zA-Z0-9_\-]+/[a-zA-Z0-9_\-]+|';
    return preg_match($pattern, $uri) === 1;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://github.com/vendor/name';
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {
    // Parse the URI to get the vendor and name of the repository.
    $all_parts = parse_url($uri);
    $parts = explode('/', $all_parts['path']);

    // Set up authentication for Github API.
    $this->setAuthentication();

    // Get the repository metadata from the API.
    try {
      $repo = $this->client->api('repo')->show($parts[1], $parts[2]);
    }
    catch (\Throwable $th) {
      // $this->messenger->addMessage($this->t('GitHub error: @error', [
      //  '@error' => $th->getMessage(),
      // ]));
      return [];
    }

    // Parse and map repository metadata to a common format.
    return $this->mapToCommonFormat($repo['full_name'], $repo['name'], $repo['description'], $repo['open_issues'], $uri);
  }

  /**
   * Add authetication stuff to GitHub client.
   */
  protected function setAuthentication(): void {
    $this->client = new Client();

    // The authenticate() method does not actually call the GitHub API,
    // rather it only stores the authentication info in $client for use when
    // $client makes an API call that requires authentication.
    $this->client->authenticate('', '', AuthMethod::CLIENT_ID);
  }

}
