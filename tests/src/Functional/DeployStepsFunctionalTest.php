<?php

declare(strict_types=1);

namespace Drupal\Tests\deploy_steps\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of DeployStepsService.
 *
 * @coversDefaultClass \Drupal\deploy_steps\Form\DeployStepsForm
 *
 * @group deploy_steps
 */
class DeployStepsFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['deploy_steps'];

  /**
   * Tests the functionality of the getText method.
   */
  public function testGetText() {
    $user = $this->createUser(['administer site configuration']);
    if (!$user instanceof AccountInterface) {
      throw new \Exception('User could not be created.');
    }
    $this->drupalLogin($user);

    $this->drupalGet('admin/config/development/deploy_steps');

    $edit = [
      'text' => '<p>This is test content.</p>',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusMessageContains('The configuration options have been saved.');

    $this->drupalGet('<front>');
    $this->assertSession()->responseContains('<noscript>This is test content.</noscript>');
  }

}
