<?php

declare(strict_types = 1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

//use Drupal\field\Entity\FieldConfig;
//use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
//use Drupal\Tests\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;
use Drupal\user\Entity\User;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class AddYmlRepoTest extends BrowserTestBase {
//  use RepositoryContentTypeTrait;

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

//    $this->createRepositoryContentType();
//
//    // Create Repository URL field on the user entity.
//    FieldStorageConfig::create([
//      'field_name' => 'field_repository_url',
//      'type' => 'link',
//      'entity_type' => 'user',
//      'cardinality' => -1,
//    ])->save();
//    FieldConfig::create([
//      'field_name' => 'field_repository_url',
//      'entity_type' => 'user',
//      'bundle' => 'user',
//      'label' => 'Repository URL',
//    ])->save();

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
  }

}
