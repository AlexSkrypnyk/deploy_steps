<?php

declare(strict_types=1);

namespace Drupal\deploy_steps_example_advanced\Plugin\DeployStep;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Indexes pending Search API items on every deploy via `search-api:index`.
 *
 * A second bulk-work example: `search-api:index` builds a Drupal batch, which
 * Drush processes across `batch:process` subprocesses that restart as memory
 * fills, so even a large backlog indexes within memory bounds. Indexing the
 * pending items on every deploy is idempotent.
 */
#[DeployStep(
  id: 'reindex_search_api',
  label: new TranslatableMarkup('Index pending Search API items'),
  weight: 20,
  phase: DeployStepInterface::PHASE_POST,
)]
final class ReindexSearchApi extends DeployStepBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a ReindexSearchApi object.
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
    // `search-api:index` is provided by the search_api module.
    return $this->moduleHandler->moduleExists('search_api') ? NULL : 'search_api module is not enabled';
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function run(): void {
    // Index pending items into every index. Search API builds the batch and
    // Drush re-spawns subprocesses to process it.
    $this->drush('search-api:index', [], ['batch-size' => 100]);
  }

}
