<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps_example\Unit;

use Drupal\Core\Site\Settings;
use Drupal\deploy_steps_example\Plugin\DeployStep\RunExternalCommandDeployStep;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the RunExternalCommandDeployStep example deploy step.
 *
 * The pattern to copy for a step that shells out: mock exec() so the real
 * process never runs, then assert the command the step would execute.
 *
 * @group DeployStep
 */
class RunExternalCommandDeployStepTest extends UnitTestCase {

  /**
   * Tests that run() shells out to the configured command.
   */
  public function testRun(): void {
    new Settings(['deploy_steps_example_command' => '/opt/deploy/post-deploy.sh']);

    $step = $this->getMockBuilder(RunExternalCommandDeployStep::class)
      ->setConstructorArgs([[], 'run_external_command', []])
      ->onlyMethods(['exec'])
      ->getMock();
    $step->expects($this->once())
      ->method('exec')
      ->with('/opt/deploy/post-deploy.sh');

    $step->run();
  }

  /**
   * Tests the skip reason for each environment and command-setting state.
   *
   * @dataProvider dataProviderSkip
   */
  public function testSkip(string $environment, string $command, ?string $expected): void {
    new Settings(['environment' => $environment, 'deploy_steps_example_command' => $command]);
    $step = new RunExternalCommandDeployStep([], 'run_external_command', []);

    $this->assertSame($expected, $step->skip());
  }

  /**
   * Data provider for testSkip().
   */
  public static function dataProviderSkip(): \Iterator {
    // The local environment skips even with a valid command.
    yield 'local environment' => ['local', __FILE__, 'local environment'];
    yield 'unset command' => ['prod', '', 'no external deploy command configured'];
    yield 'missing file' => ['prod', '/does/not/exist.sh', 'external deploy command not found: /does/not/exist.sh'];
    // An existing file (this test file) in a non-local environment runs.
    yield 'present file' => ['prod', __FILE__, NULL];
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
