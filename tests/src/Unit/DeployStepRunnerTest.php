<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Unit;

use PHPUnit\Framework\Attributes\Group;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\DeployStepManager;
use Drupal\deploy_steps\DeployStepRunner;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the DeployStepRunner.
 *
 * @group DeployStep
 */
#[Group('DeployStep')]
class DeployStepRunnerTest extends UnitTestCase {

  /**
   * Tests that steps without a skip reason run and steps with one are skipped.
   */
  public function testRunsStepsAndSkipsThoseWithReason(): void {
    $running = $this->createMock(DeployStepInterface::class);
    $running->method('skip')->willReturn(NULL);
    $running->method('label')->willReturn('running step');
    $running->expects($this->once())->method('run');

    $skipped = $this->createMock(DeployStepInterface::class);
    $skipped->method('skip')->willReturn('production environment');
    $skipped->method('label')->willReturn('skipped step');
    $skipped->expects($this->never())->method('run');

    $manager = $this->createMock(DeployStepManager::class);
    $manager->method('getSortedSteps')->willReturn(['running' => $running, 'skipped' => $skipped]);

    $runner = new DeployStepRunner($manager, $this->createMock(LoggerInterface::class));
    $runner->run(DeployStepInterface::PHASE_POST);
  }

  /**
   * Tests that a failing step aborts the run by propagating the exception.
   */
  public function testStepFailureAborts(): void {
    $failing = $this->createMock(DeployStepInterface::class);
    $failing->method('skip')->willReturn(NULL);
    $failing->method('label')->willReturn('failing step');
    $failing->method('run')->willThrowException(new \RuntimeException('Step failed.'));

    $manager = $this->createMock(DeployStepManager::class);
    $manager->method('getSortedSteps')->willReturn(['failing' => $failing]);

    $runner = new DeployStepRunner($manager, $this->createMock(LoggerInterface::class));

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Step failed.');

    $runner->run(DeployStepInterface::PHASE_POST);
  }

  /**
   * Tests that a NULL phase runs every phase in order (pre, then post).
   */
  public function testRunsAllPhasesWhenNoPhaseGiven(): void {
    $pre = $this->createMock(DeployStepInterface::class);
    $pre->method('skip')->willReturn(NULL);
    $pre->method('label')->willReturn('pre step');
    $pre->expects($this->once())->method('run');

    $post = $this->createMock(DeployStepInterface::class);
    $post->method('skip')->willReturn(NULL);
    $post->method('label')->willReturn('post step');
    $post->expects($this->once())->method('run');

    $manager = $this->createMock(DeployStepManager::class);
    $manager->method('getSortedSteps')->willReturnMap([
      [DeployStepInterface::PHASE_PRE, ['pre' => $pre]],
      [DeployStepInterface::PHASE_POST, ['post' => $post]],
    ]);

    $runner = new DeployStepRunner($manager, $this->createMock(LoggerInterface::class));
    $runner->run();
  }

}
