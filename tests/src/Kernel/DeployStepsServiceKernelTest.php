<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Kernel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\deploy_steps\DeployStepsService;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the DeployStepsService class.
 *
 * @group deploy_steps
 */
class DeployStepsServiceKernelTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['deploy_steps'];

  /**
   * The DeployStepsService instance.
   *
   * @var \Drupal\deploy_steps\DeployStepsService
   */
  protected $yourExtensionService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $your_config_config = $this->prophesize(ImmutableConfig::class);
    $your_config_config->get('text')
      ->willReturn('<p>This is <strong>bold</strong> text.</p>');

    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('deploy_steps.settings')
      ->willReturn($your_config_config->reveal());

    $this->yourExtensionService = new DeployStepsService($config_factory->reveal());
  }

  /**
   * Tests the getText method of DeployStepsService.
   */
  public function testGetText() {
    // Get the text using the service.
    $text = $this->yourExtensionService->getText();

    // Assert that the text is sanitized and contains no HTML tags.
    $this->assertEquals('This is bold text.', $text);
  }

}
