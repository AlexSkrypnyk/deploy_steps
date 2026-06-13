<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Kernel;

use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\DeployStepManager;
use Drupal\deploy_steps\DeployStepRunner;
use Drupal\KernelTests\KernelTestBase;
use Psr\Log\LoggerInterface;

/**
 * Tests deploy step discovery and the runner against a real site.
 *
 * @group DeployStep
 */
class DeployStepKernelTest extends KernelTestBase {

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
    $this->assertArrayHasKey('import_migrations', $steps);
    $this->assertArrayHasKey('reindex_search_api', $steps);
    $this->assertArrayHasKey('run_external_command', $steps);
  }

  /**
   * Tests that the runner discovers and skips steps with unmet prerequisites.
   */
  public function testRunnerSkipsStepsWithUnmetPrerequisites(): void {
    $this->setSetting('environment', 'local');

    $reasons = [];
    $logger = $this->createMock(LoggerInterface::class);
    $logger->method('notice')->willReturnCallback(function (string|\Stringable $message, array $context = []) use (&$reasons): void {
      if (isset($context['@reason'])) {
        $reasons[] = (string) $context['@reason'];
      }
    });
    $runner = new DeployStepRunner($this->container->get('plugin.manager.deploy_step'), $logger);

    $runner->run(DeployStepInterface::PHASE_POST);

    // On a bare site every example step skips: the migrate_tools and search_api
    // modules are absent, the environment is local, and no command is set.
    $this->assertContains('migrate_tools module is not enabled', $reasons);
    $this->assertContains('search_api module is not enabled', $reasons);
    $this->assertContains('local environment', $reasons);
  }

}
