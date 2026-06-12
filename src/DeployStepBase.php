<?php

declare(strict_types=1);

namespace Drupal\deploy_steps;

use Consolidation\SiteProcess\SiteProcess;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Site\Settings;
use Drush\Drush;

/**
 * Base class for deploy step plugins.
 *
 * Provides weight/phase/label accessors from the plugin definition, a default
 * "always run" gate, and environment helpers most gates need. Subclasses
 * implement ::run() and, when conditional, override ::gate().
 */
abstract class DeployStepBase extends PluginBase implements DeployStepInterface {

  /**
   * {@inheritdoc}
   */
  public function gate(): ?string {
    // Run by default. Override to skip under specific conditions.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return is_array($this->pluginDefinition) ? (int) ($this->pluginDefinition['weight'] ?? 0) : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhase(): string {
    return is_array($this->pluginDefinition) ? (string) ($this->pluginDefinition['phase'] ?? self::PHASE_POST) : self::PHASE_POST;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return is_array($this->pluginDefinition) ? (string) ($this->pluginDefinition['label'] ?? $this->getPluginId()) : $this->getPluginId();
  }

  /**
   * Returns the current environment machine name.
   *
   * Reads $settings['environment'] (Settings::get('environment')). A site sets
   * this in settings.php; it is empty when the site does not define it.
   *
   * @return string
   *   The environment machine name (e.g. local, ci, dev, stage, prod), or an
   *   empty string when not set.
   *
   * @SuppressWarnings("PHPMD.StaticAccess")
   */
  protected function environment(): string {
    return (string) Settings::get('environment', '');
  }

  /**
   * Whether the current environment is production.
   *
   * Treats the 'prod' environment marker as production. Override this method
   * if a site uses a different production marker.
   *
   * @return bool
   *   TRUE when running in the production environment.
   */
  protected function isProduction(): bool {
    return $this->environment() === 'prod';
  }

  /**
   * Runs a drush sub-command in its own process.
   *
   * Use this for heavy or long-running work (migrations, source-DB import, bulk
   * reindex): the sub-command runs in a fresh process with its own memory
   * ceiling and bootstrap, output is streamed to the deploy log, the timeout is
   * disabled, and a non-zero exit throws - aborting the deploy.
   *
   * Memory/timeout safety for the long-running case is owned by the invoked
   * command: a command that builds a Drupal batch (migrate:import,
   * search-api:index) is processed by Drush across subprocesses that restart as
   * memory fills up, the same way a sandboxed hook_update_N() is re-entered.
   *
   * @param string $command
   *   The drush command name, e.g. 'migrate:import'.
   * @param array $args
   *   Positional command arguments.
   * @param array $options
   *   Command options, e.g. ['limit' => 50].
   *
   * @return \Consolidation\SiteProcess\SiteProcess
   *   The completed process.
   *
   * @SuppressWarnings("PHPMD.StaticAccess")
   *
   * @codeCoverageIgnore
   */
  protected function drush(string $command, array $args = [], array $options = []): SiteProcess {
    $process = Drush::drush(Drush::aliasManager()->getSelf(), $command, $args, $options + Drush::redispatchOptions());
    $process->setTimeout(NULL);
    $process->mustRun($process->showRealtime());

    return $process;
  }

}
