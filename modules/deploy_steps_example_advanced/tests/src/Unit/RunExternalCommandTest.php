<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps_example_advanced\Unit;

use Drupal\Core\Site\Settings;
use Drupal\deploy_steps_example_advanced\Plugin\DeployStep\RunExternalCommand;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the RunExternalCommand example deploy step.
 *
 * The pattern to copy for a step that shells out: mock processRun() so the real
 * process never runs, then assert the command the step would execute.
 *
 * @group DeployStep
 */
class RunExternalCommandTest extends UnitTestCase {

  /**
   * Tests that run() shells out to the configured command.
   */
  public function testRun(): void {
    new Settings(['deploy_steps_example_command' => '/opt/deploy/post-deploy.sh']);

    $step = $this->getMockBuilder(RunExternalCommand::class)
      ->setConstructorArgs([[], 'run_external_command', []])
      ->onlyMethods(['processRun'])
      ->getMock();
    $step->expects($this->once())
      ->method('processRun')
      ->with('/opt/deploy/post-deploy.sh');

    $step->run();
  }

  /**
   * Tests the skip reason for each command-setting state.
   *
   * @dataProvider dataProviderSkip
   */
  public function testSkip(string $command, ?string $expected): void {
    new Settings(['deploy_steps_example_command' => $command]);
    $step = new RunExternalCommand([], 'run_external_command', []);

    $this->assertSame($expected, $step->skip());
  }

  /**
   * Data provider for testSkip().
   */
  public static function dataProviderSkip(): \Iterator {
    yield 'unset' => ['', 'no external deploy command configured'];
    yield 'missing file' => ['/does/not/exist.sh', 'external deploy command not found: /does/not/exist.sh'];
    // An existing file (this test file) means the step runs.
    yield 'present file' => [__FILE__, NULL];
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Reset the Settings singleton so command state does not leak between tests.
    new Settings([]);

    parent::tearDown();
  }

}
