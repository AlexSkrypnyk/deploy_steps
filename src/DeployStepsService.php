<?php

declare(strict_types=1);

namespace Drupal\deploy_steps;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class to manage insertion of text into <noscript> tag.
 */
class DeployStepsService {

  /**
   * The extension configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $yourExtensionConfig;

  /**
   * Constructs a new DeployStepsService instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->yourExtensionConfig = $config_factory->get('deploy_steps.settings');
  }

  /**
   * Inserts text into <noscript> tag.
   *
   * @return string
   *   The text to be inserted.
   */
  public function getText(): string {
    $text = $this->yourExtensionConfig->get('text');

    $text = $this->sanitize($text);

    return $text;
  }

  /**
   * Sanitizes text.
   *
   * @param string $string
   *   The string to be cleaned.
   *
   * @return string
   *   The sanitized string.
   */
  public static function sanitize(string $string): string {
    $string = strip_tags($string);

    return $string;
  }

}
