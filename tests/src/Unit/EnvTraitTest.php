<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Unit;

use Drupal\deploy_steps\EnvTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the EnvTrait helpers.
 *
 * @group DeployStep
 */
class EnvTraitTest extends UnitTestCase {

  /**
   * Tests that envGet() returns the value, or the default when unset.
   *
   * @dataProvider dataProviderEnvGet
   */
  public function testEnvGet(?string $value, string $default, string $expected): void {
    if ($value !== NULL) {
      putenv('DEPLOY_STEPS_TEST_VAR=' . $value);
    }

    $host = $this->createHost();

    $this->assertSame($expected, $this->invokeEnvGet($host, $default));
  }

  /**
   * Data provider for testEnvGet().
   */
  public static function dataProviderEnvGet(): \Iterator {
    yield 'set' => ['custom', 'fallback', 'custom'];
    yield 'unset returns default' => [NULL, 'fallback', 'fallback'];
    // A variable set to an empty string is still set, so it wins over the default.
    yield 'empty value is kept' => ['', 'fallback', ''];
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clear the test variable so it does not leak into other tests.
    putenv('DEPLOY_STEPS_TEST_VAR');

    parent::tearDown();
  }

  /**
   * Creates an object that composes the trait under test.
   *
   * @return object
   *   An anonymous object using EnvTrait.
   */
  protected function createHost(): object {
    return new class() {

      use EnvTrait;

    };
  }

  /**
   * Invokes the protected envGet() on the given object.
   *
   * @param object $host
   *   The object composing EnvTrait.
   * @param string $default
   *   The default forwarded to envGet().
   *
   * @return mixed
   *   The value returned by envGet().
   */
  protected function invokeEnvGet(object $host, string $default): mixed {
    $reflection = new \ReflectionMethod($host, 'envGet');

    return $reflection->invoke($host, 'DEPLOY_STEPS_TEST_VAR', $default);
  }

}
