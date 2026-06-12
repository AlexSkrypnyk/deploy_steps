<?php

declare(strict_types=1);

namespace Drupal\deploy_steps_example_advanced\Plugin\DeployStep;

use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Symfony\Component\Process\Process;

/**
 * Runs an external program on every deploy.
 *
 * Demonstrates calling something outside Drupal and Drush. The script path is
 * read from $settings['deploy_steps_example_script']; the step skips itself
 * when that is unset or the file is missing, so enabling the module never
 * breaks a deploy on its own. Symfony's Process is used in preference to
 * exec()/shell_exec(): it streams output, runs without a timeout for
 * long-running work, and throws on a non-zero exit - which aborts the deploy,
 * matching the run() contract.
 */
#[DeployStep(
  id: 'run_external_script',
  label: new TranslatableMarkup('Run external deploy script'),
  weight: 30,
  phase: DeployStepInterface::PHASE_POST,
)]
final class RunExternalScript extends DeployStepBase {

  /**
   * {@inheritdoc}
   */
  public function skip(): ?string {
    $script = $this->scriptPath();

    if ($script === '') {
      return 'no external deploy script configured';
    }

    if (!is_file($script)) {
      return sprintf('external deploy script not found: %s', $script);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function run(): void {
    $process = new Process([$this->scriptPath()]);
    $process->setTimeout(NULL);
    // Stream the script output to the deploy log; mustRun() throws on a
    // non-zero exit and aborts the deploy.
    $process->mustRun(static function (string $type, string $buffer): void {
      echo $buffer;
    });
  }

  /**
   * Returns the configured external deploy script path.
   *
   * @return string
   *   The path to the script, or an empty string when unset.
   *
   * @SuppressWarnings("PHPMD.StaticAccess")
   */
  protected function scriptPath(): string {
    return (string) Settings::get('deploy_steps_example_script', '');
  }

}
