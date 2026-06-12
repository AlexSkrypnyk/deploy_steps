<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Unit;

use Drupal\Core\Site\Settings;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the DeployStepBase helpers.
 *
 * @group DeployStep
 */
class DeployStepBaseTest extends UnitTestCase {

  /**
   * Tests weight, phase, label, and the default skip reason.
   */
  public function testWeightPhaseLabelAndDefaultSkip(): void {
    $step = $this->createStep([
      'weight' => 5,
      'label' => 'My step',
      'phase' => DeployStepInterface::PHASE_PRE,
    ]);

    $this->assertSame(5, $step->getWeight());
    $this->assertSame(DeployStepInterface::PHASE_PRE, $step->getPhase());
    $this->assertSame('My step', $step->label());
    $this->assertNull($step->skip(), 'The step runs by default.');
  }

  /**
   * Tests that weight, phase, and label fall back to sensible defaults.
   */
  public function testDefaultsFallBack(): void {
    $step = $this->createStep([]);

    $this->assertSame(0, $step->getWeight());
    $this->assertSame(DeployStepInterface::PHASE_POST, $step->getPhase());
    $this->assertSame('test_step', $step->label());
  }

  /**
   * Tests environment detection.
   *
   * @dataProvider dataProviderEnvironment
   */
  public function testEnvironment(string $value, bool $expected_production): void {
    new Settings(['environment' => $value]);
    $step = $this->createStep([]);

    $this->assertSame($value, $this->invoke($step, 'environment'));
    $this->assertSame($expected_production, $this->invoke($step, 'isProduction'));
  }

  /**
   * Data provider for testEnvironment().
   */
  public static function dataProviderEnvironment(): \Iterator {
    yield 'production' => ['prod', TRUE];
    yield 'local' => ['local', FALSE];
    yield 'ci' => ['ci', FALSE];
    yield 'stage' => ['stage', FALSE];
    yield 'dev' => ['dev', FALSE];
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Reset the Settings singleton to an empty instance so environment state
    // does not leak into other tests that share the same process.
    new Settings([]);

    parent::tearDown();
  }

  /**
   * Creates a concrete deploy step with the given plugin definition.
   *
   * @param array $definition
   *   The plugin definition (may contain 'weight', 'phase' and 'label').
   *
   * @return \Drupal\deploy_steps\DeployStepBase
   *   A concrete deploy step instance.
   */
  protected function createStep(array $definition): DeployStepBase {
    return new class([], 'test_step', $definition) extends DeployStepBase {

      /**
       * {@inheritdoc}
       */
      public function run(): void {
      }

    };
  }

  /**
   * Invokes a protected method on the given object.
   *
   * @param object $object
   *   The object to invoke the method on.
   * @param string $method
   *   The protected method name.
   *
   * @return mixed
   *   The method return value.
   */
  protected function invoke(object $object, string $method): mixed {
    $reflection = new \ReflectionMethod($object, $method);

    return $reflection->invoke($object);
  }

}
