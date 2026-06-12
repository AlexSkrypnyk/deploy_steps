<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Unit;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\deploy_steps\Drush\Commands\DeployStepCommands;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\DeployStepRunner;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the DeployStepCommands runner hooks.
 *
 * @package Drupal\deploy_steps\Tests
 */
#[Group('DeployStep')]
class DeployStepCommandsTest extends UnitTestCase {

  /**
   * Tests that the pre-command hook runs the PRE phase.
   */
  public function testPreHookRunsPrePhase(): void {
    $runner = $this->createMock(DeployStepRunner::class);
    $runner->expects($this->once())->method('run')->with(DeployStepInterface::PHASE_PRE);

    $commands = new DeployStepCommands($runner);
    $commands->runPreDeploySteps($this->createMock(CommandData::class));
  }

  /**
   * Tests that the post-command hook runs the POST phase.
   */
  public function testPostHookRunsPostPhase(): void {
    $runner = $this->createMock(DeployStepRunner::class);
    $runner->expects($this->once())->method('run')->with(DeployStepInterface::PHASE_POST);

    $commands = new DeployStepCommands($runner);
    $commands->runPostDeploySteps(NULL, $this->createMock(CommandData::class));
  }

}
