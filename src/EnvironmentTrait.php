<?php

declare(strict_types=1);

namespace Drupal\deploy_steps;

use Drupal\Core\Site\Settings;

/**
 * Reads the current environment and decides whether it is production.
 *
 * Opt-in capability for steps whose skip condition depends on the environment.
 * A step composes it with `use EnvironmentTrait;` and calls ::environment() or
 * ::isProduction(); it is kept out of DeployStepBase so the helpers are pulled
 * in only where they are needed.
 */
trait EnvironmentTrait {

  /**
   * Returns the current environment machine name.
   *
   * Reads $settings['environment'] (Settings::get('environment')). A site sets
   * this in settings.php; it is empty when the site does not define it.
   *
   * @return string
   *   The environment machine name (e.g. local, ci, dev, stage, prod), or an
   *   empty string when not set.
   *
   * @SuppressWarnings("PHPMD.StaticAccess")
   */
  protected function environment(): string {
    return (string) Settings::get('environment', '');
  }

  /**
   * Whether the current environment is production.
   *
   * Treats the 'prod' environment marker as production. Override this method
   * if a site uses a different production marker.
   *
   * @return bool
   *   TRUE when running in the production environment.
   */
  protected function isProduction(): bool {
    return $this->environment() === 'prod';
  }

}
