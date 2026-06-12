<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Unit;

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
class DeployStepRunnerTest extends UnitTestCase {

  /**
   * Tests that open steps run and gated steps are skipped.
   */
  public function testRunsOpenStepsAndSkipsGatedSteps(): void {
    $open = $this->createMock(DeployStepInterface::class);
    $open->method('gate')->willReturn(NULL);
    $open->method('label')->willReturn('open step');
    $open->expects($this->once())->method('run');

    $gated = $this->createMock(DeployStepInterface::class);
    $gated->method('gate')->willReturn('production environment');
    $gated->method('label')->willReturn('gated step');
    $gated->expects($this->never())->method('run');

    $manager = $this->createMock(DeployStepManager::class);
    $manager->method('getSortedSteps')->willReturn(['open' => $open, 'gated' => $gated]);

    $runner = new DeployStepRunner($manager, $this->createMock(LoggerInterface::class));
    $runner->run(DeployStepInterface::PHASE_POST);
  }

  /**
   * Tests that a failing step aborts the run by propagating the exception.
   */
  public function testStepFailureAborts(): void {
    $failing = $this->createMock(DeployStepInterface::class);
    $failing->method('gate')->willReturn(NULL);
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
    $pre->method('gate')->willReturn(NULL);
    $pre->method('label')->willReturn('pre step');
    $pre->expects($this->once())->method('run');

    $post = $this->createMock(DeployStepInterface::class);
    $post->method('gate')->willReturn(NULL);
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
