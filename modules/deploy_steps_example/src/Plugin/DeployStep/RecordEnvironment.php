<?php

declare(strict_types=1);

namespace Drupal\deploy_steps_example\Plugin\DeployStep;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\EnvironmentTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Records the environment the most recent deploy ran against.
 *
 * A minimal, safe example deploy step that demonstrates the pattern:
 * dependency injection via create(), the environment() helper from
 * EnvironmentTrait, and an idempotent run(). It ships in the optional
 * deploy_steps_example submodule; enable that submodule to see deploy steps
 * run, then model your own steps on it by adding a DeployStep plugin to any
 * enabled module's Plugin/DeployStep/ namespace.
 */
#[DeployStep(
  id: 'record_environment',
  label: new TranslatableMarkup('Record deployment environment'),
  weight: 0,
)]
final class RecordEnvironment extends DeployStepBase implements ContainerFactoryPluginInterface {

  use EnvironmentTrait;

  /**
   * The state key the deployed environment is recorded under.
   */
  public const STATE_KEY = 'deploy_steps_example.deployed_environment';

  /**
   * Constructs a RecordEnvironment object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly StateInterface $state,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('state'));
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    $this->state->set(self::STATE_KEY, $this->environment());
  }

}
