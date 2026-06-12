<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps_example_advanced\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\deploy_steps_example_advanced\Plugin\DeployStep\ReindexSearchApi;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the ReindexSearchApi example deploy step.
 *
 * The pattern to copy for a step that redispatches a Drush command: mock drush()
 * so no real Drush runs, then assert the command the step would redispatch.
 *
 * @group DeployStep
 */
class ReindexSearchApiTest extends UnitTestCase {

  /**
   * Tests that run() redispatches the search-api:index command.
   */
  public function testRun(): void {
    $step = $this->getMockBuilder(ReindexSearchApi::class)
      ->setConstructorArgs([[], 'reindex_search_api', [], $this->createMock(ModuleHandlerInterface::class)])
      ->onlyMethods(['drush'])
      ->getMock();
    $step->expects($this->once())
      ->method('drush')
      ->with('search-api:index', [], ['batch-size' => 100]);

    $step->run();
  }

  /**
   * Tests that the step skips itself unless search_api is enabled.
   *
   * @dataProvider dataProviderSkip
   */
  public function testSkip(bool $enabled, ?string $expected): void {
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->with('search_api')->willReturn($enabled);

    $step = new ReindexSearchApi([], 'reindex_search_api', [], $module_handler);

    $this->assertSame($expected, $step->skip());
  }

  /**
   * Data provider for testSkip().
   */
  public static function dataProviderSkip(): \Iterator {
    yield 'enabled' => [TRUE, NULL];
    yield 'not enabled' => [FALSE, 'search_api module is not enabled'];
  }

  /**
   * Tests that create() injects the module handler from the container.
   */
  public function testCreate(): void {
    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')->with('module_handler')->willReturn($this->createMock(ModuleHandlerInterface::class));

    $this->assertInstanceOf(ReindexSearchApi::class, ReindexSearchApi::create($container, [], 'reindex_search_api', []));
  }

}
