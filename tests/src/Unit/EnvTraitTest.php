<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Drupal\deploy_steps\EnvTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the EnvTrait helpers.
 *
 * @group DeployStep
 */
#[Group('DeployStep')]
class EnvTraitTest extends UnitTestCase {

  /**
   * Tests that env() returns the value, or the default when unset.
   *
   * @dataProvider dataProviderEnv
   */
  #[DataProvider('dataProviderEnv')]
  public function testEnv(?string $value, string $default, string $expected): void {
    if ($value !== NULL) {
      putenv('DEPLOY_STEPS_TEST_VAR=' . $value);
    }

    $host = $this->createHost();

    $this->assertSame($expected, $this->invokeEnv($host, $default));
  }

  /**
   * Data provider for testEnv().
   */
  public static function dataProviderEnv(): \Iterator {
    yield 'set' => ['custom', 'fallback', 'custom'];
    yield 'unset returns default' => [NULL, 'fallback', 'fallback'];
    // A variable set to an empty string is still set, so it wins over the
    // default.
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
   * Invokes the protected env() on the given object.
   *
   * @param object $host
   *   The object composing EnvTrait.
   * @param string $default
   *   The default forwarded to env().
   *
   * @return mixed
   *   The value returned by env().
   */
  protected function invokeEnv(object $host, string $default): mixed {
    $reflection = new \ReflectionMethod($host, 'env');

    return $reflection->invoke($host, 'DEPLOY_STEPS_TEST_VAR', $default);
  }

}
