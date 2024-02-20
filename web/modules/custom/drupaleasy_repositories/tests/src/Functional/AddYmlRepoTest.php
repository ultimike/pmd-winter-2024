<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class AddYmlRepoTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
  ];

  /**
   * A standard, logged-in user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $authenticatedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Configure the tests to use the yml_remote plugin.
    $config = $this->config('drupaleasy_repositories.settings');
    $config->set('repositories_plugins', ['yml_remote' => 'yml_remote']);
    $config->save();

    // Create and login as a Drupal user with permission to access the
    // DrupalEasy Repositories Settings page. This is UID=2 because UID=1 is
    // created by
    // web/core/lib/Drupal/Core/Test/FunctionalTestSetupTrait::installParameters().
    // This root user can be accessed via $this->rootUser.
    $admin_user = $this->drupalCreateUser(['configure drupaleasy repositories']);
    $this->drupalLogin($admin_user);

    // Create a regular user.
    $this->authenticatedUser = $this->drupalCreateUser(['access content']);

    // Add the Repository URL field to the default user form mode.
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $entity_display_repository->getFormDisplay('user', 'user', 'default')
      ->setComponent('field_repository_url', ['type' => 'link_default'])
      ->save();
  }

  /**
   * Test that the settings page can be reached and works as expected.
   *
   * This tests that an admin user can access the settings page, select a plugin
   * to enable, and submit the page successfully.
   *
   * @return void
   *   Returns nothing.
   *
   * @test
   */
  public function testSettingsPage(): void {
    // Get a handle on the browsing session.
    $session = $this->assertSession();

    // Navigate to the DrupalEasy repositories settings page and confirm we can
    // reach it.
    $this->drupalGet('admin/config/services/repositories');
    $session->statusCodeEquals(200);

    // Select the "Yml remote" checkbox and submit the form.
    $edit = [
      'edit-repositories-plugins-yml-remote' => 'yml_remote',
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->responseContains('The configuration options have been saved.');
    $session->statusMessageNotExists('error');
    $session->checkboxChecked('edit-repositories-plugins-yml-remote');
    $session->checkboxNotChecked('edit-repositories-plugins-github');
  }

  /**
   * Test that the settings page cannot be reached without permission.
   *
   * @return void
   *   Returns nothing.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUnprivilegedSettingsPage(): void {
    $session = $this->assertSession();
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('/admin/config/services/repositories');
    // Test to ensure that the page loads without error.
    // See https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
    $session->statusCodeEquals(403);
  }

  /**
   * Test that a yml repo can be added to profile by a user.
   *
   * This tests that a yml-based repo can be added to a user's profile and
   * that a repository node is successfully created upon saving the profile.
   *
   * @test
   */
  public function testAddYmlRepo(): void {
    // Get a handle on the browsing session.
    $session = $this->assertSession();

    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('user/' . $this->authenticatedUser->id() . '/edit');
    $session->statusCodeEquals(200);

    // Get the full path to the test .yml file.
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = \Drupal::service('module_handler');
    $module = $module_handler->getModule('drupaleasy_repositories');
    $module_full_path = \Drupal::request()->getUri() . $module->getPath();

    // Add our test .yml file to the edit array.
    $edit = [
      'field_repository_url[0][uri]' => $module_full_path . '/tests/assets/batman-repo.yml',
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
    $session->responseContains('The changes have been saved.');
    // We can't check for the following message unless we also have the future
    // drupaleasy_notify module enabled.
    // phpcs:ignore
    // $session->responseContains('The repo named <em class="placeholder">The Batman repository</em> has been created');

    // Find the repository node created.
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'repository');
    $results = $query->accessCheck(FALSE)->execute();
    $session->assert(count($results) === 1, 'Either 0 or more than 1 repository nodes were found.');

    $entity_type_manager = \Drupal::entityTypeManager();
    $node_storage = $entity_type_manager->getStorage('node');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load(reset($results));

    $session->assert($node->field_machine_name->value === 'batman-repo', 'Machine name does not match.');
    $session->assert($node->field_source->value === 'yml_remote', 'Source does not match.');
    $session->assert($node->getTitle() === 'The Batman Repository', 'Title does not match.');
    $session->assert($node->field_description->value === 'This is where Batman keeps all of his crime-fighting code.', 'Description does not match.');
    $session->assert((int) $node->field_number_of_issues->value === 6, 'Number of open issues does not match.');
  }

}
