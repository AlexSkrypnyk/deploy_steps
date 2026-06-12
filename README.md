<div align="center">
  <a href="https://www.drupal.org/project/deploy_steps" rel="noopener">
  <img width=200px height=200px src="logo.png" alt="Deploy Steps logo"></a>
</div>

<h1 align="center">Deploy Steps</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/AlexSkrypnyk/deploy_steps.svg)](https://github.com/AlexSkrypnyk/deploy_steps/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/AlexSkrypnyk/deploy_steps.svg)](https://github.com/AlexSkrypnyk/deploy_steps/pulls)
[![Build, test and deploy](https://github.com/AlexSkrypnyk/deploy_steps/actions/workflows/test.yml/badge.svg)](https://github.com/AlexSkrypnyk/deploy_steps/actions/workflows/test.yml)
[![codecov](https://codecov.io/gh/AlexSkrypnyk/deploy_steps/graph/badge.svg)](https://codecov.io/gh/AlexSkrypnyk/deploy_steps)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/AlexSkrypnyk/deploy_steps)
![LICENSE](https://img.shields.io/github/license/AlexSkrypnyk/deploy_steps)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

![PHP 8.2](https://img.shields.io/badge/PHP-8.2-777BB4.svg)
![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4.svg)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4.svg)
![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777BB4.svg)
![Drupal 10](https://img.shields.io/badge/Drupal-10-009CDE.svg)
![Drupal 11](https://img.shields.io/badge/Drupal-11-006AA9.svg)

</div>

---

Runs repeatable, run-on-every-deploy logic as discoverable **deploy step** plugins.

## Why this module exists

Drupal and Drush run-once hooks (`hook_update_N()`, `hook_post_update_NAME()`, `hook_deploy_NAME()`) are recorded as completed and never run again - they cannot express "run on every deploy". This module provides that missing layer: the repeatable counterpart to run-once `hook_deploy_NAME()`.

It owns the single pair of Drush `pre-command` / `post-command` hooks on `deploy:hook`, and on every deploy it **discovers** every `DeployStep` plugin from every enabled module, groups them by phase, orders each phase by weight, checks each plugin's skip reason, and runs the rest. Any enabled module contributes steps just by declaring a plugin - no Drush wiring of its own - which is what makes the mechanism reusable.

## Requirements

- Drupal `^10.3 || ^11`
- PHP `8.2+`
- [Drush](https://www.drush.org/) `^12.5 || ^13` - the module's entire integration is a pair of Drush command hooks

## Installation

```bash
composer require drupal/deploy_steps
drush pm:install deploy_steps
```

To enable the bundled example step (see below):

```bash
drush pm:install deploy_steps_example
```

## How it runs

The module hooks `drush deploy:hook` - the command a deploy pipeline runs in every environment to apply pending database updates and configuration. Pre-phase steps run before the `deploy:hook` body, post-phase steps after it.

If a site's deploy pipeline does **not** call `drush deploy:hook`, the steps do not fire. Wire `drush deploy:hook` (typically via `drush deploy`) into your deploy process to use this module.

## Adding a deploy step

Create a plugin in any enabled module's `src/Plugin/DeployStep/` namespace:

```php
namespace Drupal\my_module\Plugin\DeployStep;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\EnvironmentTrait;

#[DeployStep(
  id: 'rebuild_search_index',
  label: new TranslatableMarkup('Rebuild the search index'),
  weight: 10,
  phase: DeployStepInterface::PHASE_POST,
)]
final class RebuildSearchIndex extends DeployStepBase {

  // Opt in to the environment helpers used by skip() below.
  use EnvironmentTrait;

  // Return NULL to run, or a human-readable reason to skip (logged verbatim).
  public function skip(): ?string {
    return $this->isProduction() ? 'production environment' : NULL;
  }

  public function run(): void {
    // Idempotent work - it runs on every deploy.
  }

}
```

- **`weight`** sets the run order within the phase (lower runs first).
- **`phase`** chooses when the step runs: `PHASE_PRE` (before the `deploy:hook` body) or `PHASE_POST` (after it, the default).
- **`skip()`** decides whether the step runs. Returning a *reason* instead of a bare boolean means every skip is explicit and explained in the deploy log. The `environment()` / `isProduction()` helpers from `EnvironmentTrait` (composed with `use`) cover the common case.
- **`run()`** is the step. It must be idempotent; throw to abort the deploy.
- Inject services with `ContainerFactoryPluginInterface::create()`, like any Drupal plugin (see the `RecordEnvironment` example in the `deploy_steps_example` submodule).

A single module can declare as many steps as it needs - each is its own plugin with its own ID.

### Long-running and memory-bound work

`DrushCommandTrait` provides a `drush()` helper for heavy work (migrations, source-DB import, bulk reindex); a step composes it with `use`. It runs the given Drush sub-command in its own process - a fresh memory ceiling and bootstrap, output streamed to the deploy log, no timeout, and a non-zero exit throws to abort the deploy. Commands that build a Drupal batch (`migrate:import`, `search-api:index`) are then processed by Drush across subprocesses that restart as memory fills up, the same way a sandboxed `hook_update_N()` is re-entered.

### The environment convention

`environment()` reads `$settings['environment']` (set in `settings.php`), and `isProduction()` treats the `'prod'` value as production. Both live in `EnvironmentTrait`, which a step composes with `use`. If your site uses a different production marker, override `isProduction()` in your step (or its base class). The module does not hardcode any project-specific environment names.

## Example submodules

Two optional submodules demonstrate the patterns - enable whichever you want to study, then model your own steps on them.

`deploy_steps_example` ships a single `RecordEnvironment` step that records the current environment to State on every deploy. It is the minimal, safe demonstration of the pattern: dependency injection via `create()`, the `environment()` helper, and an idempotent `run()`.

`deploy_steps_example_advanced` demonstrates real-world deploy work. Each step skips itself when its prerequisite is missing, so enabling the module never breaks a deploy on its own:

- `ImportMigrations` redispatches `migrate:import --all --update` (skipped unless the `migrate_tools` module is enabled).
- `ReindexSearchApi` redispatches `search-api:index` (skipped unless the `search_api` module is enabled).
- `RunExternalScript` runs an external program via Symfony's `Process` (skipped unless `$settings['deploy_steps_example_script']` points at an existing file).

`ImportMigrations` and `ReindexSearchApi` show the bulk-work pattern - each redispatched command builds a Drupal batch that Drush processes across restarting subprocesses (see [Long-running and memory-bound work](#long-running-and-memory-bound-work) above). `ReindexSearchApi` uses `search_api`, listed under `suggest`. `ImportMigrations` uses `migrate_tools`, which needs `migrate_plus` to enable:

```bash
composer require drupal/search_api
composer require drupal/migrate_tools drupal/migrate_plus
```

`RunExternalScript` shells out to a non-Drush program with Symfony's `Process` (preferred over raw `exec()`/`shell_exec()` because it streams output and throws on a non-zero exit). Point the setting at an executable to enable it:

```php
// settings.php
$settings['deploy_steps_example_script'] = '/path/to/post-deploy.sh';
```

## Local development

1. Install PHP with SQLite support and Composer
2. Clone this repository
3. Run `make build` or `ahoy build`

## Building website

`make build` or `ahoy build` assembles the codebase, starts the PHP server
and provisions the Drupal website with this extension enabled. These operations
are executed using scripts within [`.devtools`](.devtools) directory. CI uses
the same scripts to build and test this extension.

The resulting codebase is then placed in the `build` directory. The extension
files are symlinked into the Drupal site structure.

The `build` command is a wrapper for more granular commands:
```bash
make assemble     # Assemble the codebase
make start        # Start the PHP server
make provision    # Provision the Drupal website

ahoy assemble     # Assemble the codebase
ahoy start        # Start the PHP server
ahoy provision    # Provision the Drupal website
```

The `provision` command is useful for re-installing the Drupal website without
re-assembling the codebase.

### Drupal versions

The Drupal version used for the codebase assembly is determined by the
`DRUPAL_VERSION` variable and defaults to the latest stable version.

You can specify a different version by setting the `DRUPAL_VERSION` environment
variable before running the `make build` or `ahoy build` command:

```bash
DRUPAL_VERSION=11 make build        # Drupal 11
DRUPAL_VERSION=11@alpha make build  # Drupal 11 alpha
DRUPAL_VERSION=10@beta make build   # Drupal 10 beta
DRUPAL_VERSION=11.1 make build      # Drupal 11.1
```

## Coding standards

The `make lint` or `ahoy lint` command checks the codebase using PHPCS
(`Drupal` and `DrupalPractice` standards), PHPStan, Drupal Rector and Twig CS
Fixer. The configuration files for these tools are located in the root of the
codebase. Run `make lint-fix` or `ahoy lint-fix` to auto-fix what can be fixed.

## Testing

The `make test` or `ahoy test` command runs the tests for this extension.

The tests are located in the `tests/src` directory. The `phpunit.xml` file
configures PHPUnit to run the tests.

```bash
make test-unit                    # Run Unit tests
make test-kernel                  # Run Kernel tests

ahoy test-unit                    # Run Unit tests
ahoy test-kernel                  # Run Kernel tests
```

---
_This repository was created using the [Drupal Extension Scaffold](https://github.com/AlexSkrypnyk/drupal_extension_scaffold) project template_
