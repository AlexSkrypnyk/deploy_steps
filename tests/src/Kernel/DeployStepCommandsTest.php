<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Kernel;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\deploy_steps\Drush\Commands\DeployStepCommands;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\DeployStepManager;
use Drupal\deploy_steps\DeployStepRunner;
use Drupal\deploy_steps_example\Plugin\DeployStep\RecordEnvironment;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests deploy step discovery, the runner, and the command on a site.
 *
 * @package Drupal\deploy_steps\Tests
 */
#[Group('DeployStep')]
class DeployStepCommandsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['deploy_steps', 'deploy_steps_example'];

  /**
   * Tests that the manager discovers plugins from enabled modules.
   */
  public function testManagerDiscoversSteps(): void {
    $manager = $this->container->get('plugin.manager.deploy_step');
    $this->assertInstanceOf(DeployStepManager::class, $manager);

    $steps = $manager->getSortedSteps(DeployStepInterface::PHASE_POST);
    $this->assertArrayHasKey('record_environment', $steps);
  }

  /**
   * Tests that the runner executes discovered steps against a real site.
   */
  public function testRunnerExecutesSteps(): void {
    $this->setSetting('environment', 'ci');

    $this->container->get(DeployStepRunner::class)->run();

    $this->assertSame('ci', $this->container->get('state')->get(RecordEnvironment::STATE_KEY));
  }

  /**
   * Tests that the command's post hook runs the discovered steps.
   */
  public function testCommandRunsPostPhase(): void {
    $this->setSetting('environment', 'ci');

    $commands = DeployStepCommands::create($this->container);
    $commands->runPostDeploySteps(NULL, $this->createMock(CommandData::class));

    $this->assertSame('ci', $this->container->get('state')->get(RecordEnvironment::STATE_KEY));
  }

}
