<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps_example\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\deploy_steps_example\Plugin\DeployStep\ReindexSearchApiDeployStep;

/**
 * Tests the ReindexSearchApiDeployStep example deploy step.
 *
 * The pattern to copy for a step that redispatches a Drush command: mock
 * drush() so no real Drush runs, then assert the command the step would
 * redispatch.
 *
 * @group DeployStep
 */
class ReindexSearchApiDeployStepTest extends DeployStepUnitTestBase {

  /**
   * Tests that run() redispatches the search-api:index command.
   */
  public function testRun(): void {
    $step = $this->getMockBuilder(ReindexSearchApiDeployStep::class)
      ->setConstructorArgs([[], 'reindex_search_api', []])
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

    $step = ReindexSearchApiDeployStep::create($this->container(['module_handler' => $module_handler]), [], 'reindex_search_api', []);

    $this->assertSame($expected, $step->skip());
  }

  /**
   * Data provider for testSkip().
   */
  public static function dataProviderSkip(): \Iterator {
    yield 'enabled' => [TRUE, NULL];
    yield 'not enabled' => [FALSE, 'search_api module is not enabled'];
  }

}
