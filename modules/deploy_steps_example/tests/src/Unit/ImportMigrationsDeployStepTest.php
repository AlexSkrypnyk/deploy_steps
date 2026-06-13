<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps_example\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\deploy_steps_example\Plugin\DeployStep\ImportMigrationsDeployStep;

/**
 * Tests the ImportMigrationsDeployStep example deploy step.
 *
 * Two patterns to copy: mock drush() to assert the redispatched command without
 * running real Drush, and drive a step's behaviour from environment variables
 * with putenv() (the way a deploy pipeline configures it).
 *
 * @group DeployStep
 */
class ImportMigrationsDeployStepTest extends DeployStepUnitTestBase {

  /**
   * Tests that environment variables shape the migrate:import options.
   *
   * @dataProvider dataProviderRun
   */
  public function testRun(array $environment, array $expected_options): void {
    // Isolate from any ambient DRUPAL_MIGRATION_* values so the 'defaults'
    // dataset reads the step's defaults, not the host environment.
    putenv('DRUPAL_MIGRATION_IMPORT_LIMIT');
    putenv('DRUPAL_MIGRATION_UPDATE');

    foreach ($environment as $name => $value) {
      putenv($name . '=' . $value);
    }

    $step = $this->getMockBuilder(ImportMigrationsDeployStep::class)
      ->setConstructorArgs([[], 'import_migrations', []])
      ->onlyMethods(['drush'])
      ->getMock();
    $step->expects($this->once())
      ->method('drush')
      ->with('migrate:import', [], $expected_options);

    $step->run();
  }

  /**
   * Data provider for testRun().
   */
  public static function dataProviderRun(): \Iterator {
    yield 'defaults' => [[], ['all' => TRUE, 'limit' => 50]];
    yield 'custom limit and update' => [
      ['DRUPAL_MIGRATION_IMPORT_LIMIT' => '10', 'DRUPAL_MIGRATION_UPDATE' => '1'],
      ['all' => TRUE, 'limit' => 10, 'update' => TRUE],
    ];
    yield 'unlimited' => [['DRUPAL_MIGRATION_IMPORT_LIMIT' => '0'], ['all' => TRUE]];
  }

  /**
   * Tests the skip reason for the migration-skip env var and module state.
   *
   * @dataProvider dataProviderSkip
   */
  public function testSkip(string $migration_skip, bool $module_enabled, ?string $expected): void {
    putenv('DRUPAL_MIGRATION_SKIP=' . $migration_skip);

    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->with('migrate_tools')->willReturn($module_enabled);

    $step = ImportMigrationsDeployStep::create($this->container(['module_handler' => $module_handler]), [], 'import_migrations', []);

    $this->assertSame($expected, $step->skip());
  }

  /**
   * Data provider for testSkip().
   */
  public static function dataProviderSkip(): \Iterator {
    yield 'skipped via env' => ['1', TRUE, 'DRUPAL_MIGRATION_SKIP is set'];
    yield 'module not enabled' => ['0', FALSE, 'migrate_tools module is not enabled'];
    yield 'enabled' => ['0', TRUE, NULL];
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clear the environment variables so they do not leak between tests.
    putenv('DRUPAL_MIGRATION_SKIP');
    putenv('DRUPAL_MIGRATION_IMPORT_LIMIT');
    putenv('DRUPAL_MIGRATION_UPDATE');

    parent::tearDown();
  }

}
