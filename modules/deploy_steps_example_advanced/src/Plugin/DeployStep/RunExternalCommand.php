<?php

declare(strict_types=1);

namespace Drupal\deploy_steps_example_advanced\Plugin\DeployStep;

use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\ProcessTrait;

/**
 * Runs an external command on every deploy.
 *
 * Demonstrates calling something outside Drupal and Drush via ProcessTrait. The
 * command path is read from $settings['deploy_steps_example_command']; the step
 * skips itself when that is unset or the file is missing, so enabling the module
 * never breaks a deploy on its own. ProcessTrait::processRun() runs the command
 * through Symfony's Process - streaming output and throwing on a non-zero exit
 * to abort the deploy.
 */
#[DeployStep(
  id: 'run_external_command',
  label: new TranslatableMarkup('Run external deploy command'),
  weight: 30,
  phase: DeployStepInterface::PHASE_POST,
)]
class RunExternalCommand extends DeployStepBase {

  use ProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function skip(): ?string {
    $command = $this->commandPath();

    if ($command === '') {
      return 'no external deploy command configured';
    }

    if (!is_file($command)) {
      return sprintf('external deploy command not found: %s', $command);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    $this->processRun($this->commandPath());
  }

  /**
   * Returns the configured external deploy command path.
   *
   * @return string
   *   The path to the command, or an empty string when unset.
   *
   * @SuppressWarnings("PHPMD.StaticAccess")
   */
  protected function commandPath(): string {
    return (string) Settings::get('deploy_steps_example_command', '');
  }

}
