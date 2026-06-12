<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Unit;

use Drupal\Core\Site\Settings;
use Drupal\deploy_steps\EnvironmentTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the EnvironmentTrait helpers.
 *
 * @group DeployStep
 */
class EnvironmentTraitTest extends UnitTestCase {

  /**
   * Tests environment detection.
   *
   * @dataProvider dataProviderEnvironment
   */
  public function testEnvironment(string $value, bool $expected_production): void {
    new Settings(['environment' => $value]);
    $host = $this->createHost();

    $this->assertSame($value, $this->invoke($host, 'environment'));
    $this->assertSame($expected_production, $this->invoke($host, 'isProduction'));
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
   * Creates an object that composes the trait under test.
   *
   * @return object
   *   An anonymous object using EnvironmentTrait.
   */
  protected function createHost(): object {
    return new class() {

      use EnvironmentTrait;

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
