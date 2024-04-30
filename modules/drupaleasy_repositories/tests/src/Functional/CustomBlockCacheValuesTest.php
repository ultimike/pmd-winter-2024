<?php

declare(strict_types=1);

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
final class CustomBlockCacheValuesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['drupaleasy_repositories', 'block', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // This will place the block on every page of the site.
    $this->drupalPlaceBlock('drupaleasy_repositories_my_repositories_stats',
      [
        'region' => 'content',
        'id' => 'drupaleasy-repositories-my-repositories-stats',
      ]
    );
  }

  /**
   * Test to ensure custom cache values are present.
   *
   * @test
   */
  public function testCustomCacheValues(): void {
    // Load the home page.
    $this->drupalGet('');

    // Set the cacheability settings of the block to the following for these
    // test to pass:
    // public function getCacheTags() {
    //  return ['node_list:repository', 'drupaleasy_repositories'];
    // }
    // public function getCacheContexts() {
    //   return ['timezone', 'user'];
    // }
    // This demonstrates that max-age does not bubble up to Cache-control
    // (external caching).
    $this->assertSession()->responseHeaderContains('Cache-Control', 'must-revalidate, no-cache, private');

    // This demonstrates that the max age does bubble up to the Internal Dynamic
    // Page Cache module.
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Max-Age', '-1');

    // These demonstrate that the cache tags were added and did bubble up.
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'drupaleasy_repositories');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'node_list:repository');

    // This demonstrates that the cache context was added and did bubble up.
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'timezone');
  }

}
