<?php

declare(strict_types=1);

namespace Drupal\deploy_steps;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for deploy step plugins.
 *
 * Provides what every step needs: the weight/phase/label accessors from the
 * plugin definition, a default ::skip() that always runs, and the common Drupal
 * services injected on every step - the module handler, state, entity type
 * manager, and config factory - so most steps need no create() of their own. A
 * step needing another service overrides ::create() and calls parent::create().
 * Specialized capabilities are opt-in traits a step composes with `use`:
 * \Drupal\deploy_steps\EnvironmentTrait for environment-conditional skips,
 * \Drupal\deploy_steps\EnvTrait for reading environment variables,
 * \Drupal\deploy_steps\DrushTrait for redispatching a Drush sub-command, and
 * \Drupal\deploy_steps\ExecTrait for running an external command. Subclasses
 * implement ::run() and, when conditional, override ::skip().
 */
abstract class DeployStepBase extends PluginBase implements DeployStepInterface, ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    // @phpstan-ignore new.static
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->state = $container->get('state');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->configFactory = $container->get('config.factory');

    return $instance;
  }

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
