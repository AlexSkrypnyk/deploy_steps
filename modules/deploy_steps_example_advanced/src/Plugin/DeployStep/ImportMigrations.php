<?php

declare(strict_types=1);

namespace Drupal\deploy_steps_example_advanced\Plugin\DeployStep;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\DrushTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imports migrations on every deploy by redispatching `migrate:import`.
 *
 * Demonstrates the bulk-work pattern: `migrate:import` builds a Drupal batch,
 * and Drush re-spawns fresh `batch:process` subprocesses as memory fills, so a
 * large import runs within memory bounds and resumes the same way a sandboxed
 * hook_update_N() is re-entered. The step only wires the command; Drush owns
 * the batching and restart behaviour.
 */
#[DeployStep(
  id: 'import_migrations',
  label: new TranslatableMarkup('Import migrations'),
  weight: 10,
  phase: DeployStepInterface::PHASE_POST,
)]
class ImportMigrations extends DeployStepBase implements ContainerFactoryPluginInterface {

  use DrushTrait;

  /**
   * Constructs an ImportMigrations object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('module_handler'));
  }

  /**
   * {@inheritdoc}
   */
  public function skip(): ?string {
    // `migrate:import` is provided by the migrate_tools module.
    return $this->moduleHandler->moduleExists('migrate_tools') ? NULL : 'migrate_tools module is not enabled';
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    // Import every migration and update previously-imported rows. Drush builds
    // and processes the batch across subprocesses.
    $this->drush('migrate:import', [], ['all' => TRUE, 'update' => TRUE]);
  }

}
