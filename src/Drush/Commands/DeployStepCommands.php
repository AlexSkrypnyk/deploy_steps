<?php

declare(strict_types=1);

namespace Drupal\deploy_steps\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\DeployStepRunner;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Runs deploy step plugins around every `drush deploy:hook`.
 *
 * Drupal/Drush run-once hooks (hook_update_N(), hook_post_update_NAME() and
 * hook_deploy_NAME()) are recorded as completed and never run again, so they
 * cannot express "run on every deploy". This command provides that missing
 * layer: it discovers every DeployStep plugin from every enabled module,
 * groups them by phase, orders each phase by weight, asks each plugin's gate
 * whether to run, and runs the rest - on every single deploy. Pre-phase
 * plugins run before the `deploy:hook` body, post-phase plugins after it.
 *
 * The design inverts the naive "one Drush command hook per module" approach,
 * which does not scale: Drush discovers command hooks at bootstrap, so a
 * module could only contribute deploy logic by shipping its own DrushCommands
 * class AND being enabled before bootstrap. Here, deploy_steps owns the single
 * command hook and DISCOVERS plugins; any enabled module contributes steps by
 * declaring a DeployStep plugin - no Drush wiring of its own. That is what
 * makes the mechanism reusable.
 *
 * The hooks target `deploy:hook`, not the higher-level `deploy` command:
 * `deploy:hook` is the command a deploy pipeline runs in every environment to
 * apply pending database updates and configuration, so it is the right anchor
 * for repeatable per-deploy work. If a site's deploy pipeline does not call
 * `deploy:hook`, the steps do not fire.
 */
final class DeployStepCommands extends DrushCommands {

  /**
   * Constructs a DeployStepCommands object.
   *
   * @param \Drupal\deploy_steps\DeployStepRunner $runner
   *   The deploy step runner.
   */
  public function __construct(
    protected readonly DeployStepRunner $runner,
  ) {
    parent::__construct();
  }

  /**
   * Creates an instance of the command handler.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return self
   *   The command handler instance.
   */
  public static function create(ContainerInterface $container): self {
    return new self($container->get(DeployStepRunner::class));
  }

  /**
   * Runs PRE-phase plugins before EVERY `drush deploy:hook`.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $command_data
   *   The command data.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  #[CLI\Hook(type: HookManager::PRE_COMMAND_HOOK, target: 'deploy:hook')]
  public function runPreDeploySteps(CommandData $command_data): void {
    $this->runner->run(DeployStepInterface::PHASE_PRE);
  }

  /**
   * Runs POST-phase plugins after EVERY `drush deploy:hook`.
   *
   * @param mixed $result
   *   The result returned by the `deploy:hook` command.
   * @param \Consolidation\AnnotatedCommand\CommandData $command_data
   *   The command data.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: 'deploy:hook')]
  public function runPostDeploySteps(mixed $result, CommandData $command_data): void {
    $this->runner->run(DeployStepInterface::PHASE_POST);
  }

}
