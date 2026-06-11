<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\deploy_steps\DeployStepsService;

/**
 * Tests the DeployStepsService class.
 *
 * @group deploy_steps
 */
class DeployStepsServiceUnitTest extends UnitTestCase {

  /**
   * Tests the sanitize method of DeployStepsService.
   *
   * @covers \Drupal\deploy_steps\DeployStepsService::sanitize
   * @dataProvider dataProviderSanitize
   */
  public function testSanitize(string $input, string $expected) {
    $this->assertEquals($expected, DeployStepsService::sanitize($input));
  }

  /**
   * Provides data for testing the sanitize method.
   */
  public static function dataProviderSanitize(): array {
    return [
      ['', ''],
      ['<p>This is <strong>bold</strong> text.</p>', 'This is bold text.'],
      ['<div><span>This is some <em>italic</em> text.</span></div>', 'This is some italic text.'],
      ['<script>alert("Hello!");</script>', 'alert("Hello!");'],
    ];
  }

}
