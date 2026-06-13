<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps_example\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for unit testing steps built through DeployStepBase::create().
 */
abstract class DeployStepUnitTestBase extends UnitTestCase {

  /**
   * Builds a container returning the services DeployStepBase::create() needs.
   *
   * @param array $services
   *   Service overrides keyed by service id; any not given is a bare mock.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The container.
   */
  protected function container(array $services = []): ContainerInterface {
    $services += [
      'module_handler' => $this->createMock(ModuleHandlerInterface::class),
      'state' => $this->createMock(StateInterface::class),
      'entity_type.manager' => $this->createMock(EntityTypeManagerInterface::class),
      'config.factory' => $this->createMock(ConfigFactoryInterface::class),
    ];

    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')->willReturnCallback(fn(string $id): object => $services[$id]);

    return $container;
  }

}
