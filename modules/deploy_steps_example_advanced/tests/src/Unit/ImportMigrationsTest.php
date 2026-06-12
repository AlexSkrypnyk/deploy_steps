<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps_example_advanced\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\deploy_steps_example_advanced\Plugin\DeployStep\ImportMigrations;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the ImportMigrations example deploy step.
 *
 * The pattern to copy for a step that redispatches a Drush command: mock drush()
 * so no real Drush runs, then assert the command the step would redispatch.
 *
 * @group DeployStep
 */
class ImportMigrationsTest extends UnitTestCase {

  /**
   * Tests that run() redispatches the migrate:import command.
   */
  public function testRun(): void {
    $step = $this->getMockBuilder(ImportMigrations::class)
      ->setConstructorArgs([[], 'import_migrations', [], $this->createMock(ModuleHandlerInterface::class)])
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

    $step = new ImportMigrations([], 'import_migrations', [], $module_handler);

    $this->assertSame($expected, $step->skip());
  }

  /**
   * Data provider for testSkip().
   */
  public static function dataProviderSkip(): \Iterator {
    yield 'enabled' => [TRUE, NULL];
    yield 'not enabled' => [FALSE, 'migrate_tools module is not enabled'];
  }

  /**
   * Tests that create() injects the module handler from the container.
   */
  public function testCreate(): void {
    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')->with('module_handler')->willReturn($this->createMock(ModuleHandlerInterface::class));

    $this->assertInstanceOf(ImportMigrations::class, ImportMigrations::create($container, [], 'import_migrations', []));
  }

}
