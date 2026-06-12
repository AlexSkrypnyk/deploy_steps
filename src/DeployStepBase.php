<?php

declare(strict_types=1);

namespace Drupal\deploy_steps;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for deploy step plugins.
 *
 * Provides only what every step needs: the weight/phase/label accessors from
 * the plugin definition and a default ::skip() that always runs. Capability
 * helpers are opt-in traits a step composes with `use` as needed -
 * \Drupal\deploy_steps\EnvironmentTrait for environment-conditional skips and
 * \Drupal\deploy_steps\DrushCommandTrait for redispatching a Drush sub-command.
 * Subclasses implement ::run() and, when conditional, override ::skip().
 */
abstract class DeployStepBase extends PluginBase implements DeployStepInterface {

  /**
   * {@inheritdoc}
   */
  public function skip(): ?string {
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

}
