<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Unit;

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

}
