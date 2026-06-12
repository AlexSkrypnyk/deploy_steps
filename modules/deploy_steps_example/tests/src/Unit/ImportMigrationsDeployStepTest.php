<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps_example\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\deploy_steps_example\Plugin\DeployStep\ImportMigrationsDeployStep;

/**
 * Tests the ImportMigrationsDeployStep example deploy step.
 *
 * The pattern to copy for a step that redispatches a Drush command: mock drush()
 * so no real Drush runs, then assert the command the step would redispatch.
 *
 * @group DeployStep
 */
class ImportMigrationsDeployStepTest extends DeployStepUnitTestBase {

  /**
   * Tests that run() redispatches the migrate:import command.
   */
  public function testRun(): void {
    $step = $this->getMockBuilder(ImportMigrationsDeployStep::class)
      ->setConstructorArgs([[], 'import_migrations', []])
      ->onlyMethods(['drush'])
      ->getMock();
    $step->expects($this->once())
      ->method('drush')
      ->with('migrate:import', [], ['all' => TRUE, 'update' => TRUE]);

    $step->run();
  }

  /**
   * Tests that the step skips itself unless migrate_tools is enabled.
   *
   * @dataProvider dataProviderSkip
   */
  public function testSkip(bool $enabled, ?string $expected): void {
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->with('migrate_tools')->willReturn($enabled);

    $step = ImportMigrationsDeployStep::create($this->container(['module_handler' => $module_handler]), [], 'import_migrations', []);

    $this->assertSame($expected, $step->skip());
  }

  /**
   * Data provider for testSkip().
   */
  public static function dataProviderSkip(): \Iterator {
    yield 'enabled' => [TRUE, NULL];
    yield 'not enabled' => [FALSE, 'migrate_tools module is not enabled'];
  }

}
