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

It owns the single pair of Drush `pre-command` / `post-command` hooks on `deploy:hook`, and on every deploy it **discovers** every `DeployStep` plugin from every enabled module, groups them by phase, orders each phase by weight, asks each plugin's gate whether to run, and runs the rest. Any enabled module contributes steps just by declaring a plugin - no Drush wiring of its own - which is what makes the mechanism reusable.

## Requirements

- Drupal `^10.3 || ^11`
- PHP `8.3+`
- [Drush](https://www.drush.org/) `^12.5 || ^13` - the module's entire integration is a pair of Drush command hooks

## Installation

```bash
composer require drupal/deploy_steps
drush pm:install deploy_steps
```

To enable the bundled example steps (see below):

```bash
drush pm:install deploy_steps_example
drush pm:install deploy_steps_example_advanced
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

#[DeployStep(
  id: 'rebuild_search_index',
  label: new TranslatableMarkup('Rebuild the search index'),
  weight: 10,
  phase: DeployStepInterface::PHASE_POST,
)]
final class RebuildSearchIndex extends DeployStepBase {

  // Return NULL to run, or a human-readable reason to skip (logged verbatim).
  public function gate(): ?string {
    return $this->isProduction() ? 'production environment' : NULL;
  }

  public function run(): void {
    // Idempotent work - it runs on every deploy.
  }

}
```

- **`weight`** sets the run order within the phase (lower runs first).
- **`phase`** chooses when the step runs: `PHASE_PRE` (before the `deploy:hook` body) or `PHASE_POST` (after it, the default).
- **`gate()`** decides whether the step runs. Returning a *reason* instead of a bare boolean means every skip is explicit and explained in the deploy log. The inherited `environment()` / `isProduction()` helpers cover the common case.
- **`run()`** is the step. It must be idempotent; throw to abort the deploy.
- Inject services with `ContainerFactoryPluginInterface::create()`, like any Drupal plugin (see the `RecordEnvironment` example in the `deploy_steps_example` submodule).

A single module can declare as many steps as it needs - each is its own plugin with its own ID.

### Long-running and memory-bound work

`DeployStepBase` provides a `drush()` helper for heavy work (migrations, source-DB import, bulk reindex). It runs the given Drush sub-command in its own process - a fresh memory ceiling and bootstrap, output streamed to the deploy log, no timeout, and a non-zero exit throws to abort the deploy. Commands that build a Drupal batch (`migrate:import`, `search-api:index`) are then processed by Drush across subprocesses that restart as memory fills up, the same way a sandboxed `hook_update_N()` is re-entered.

### The environment convention

`environment()` reads `$settings['environment']` (set in `settings.php`), and `isProduction()` treats the `'prod'` value as production. If your site uses a different production marker, override `isProduction()` in your step (or its base class). The module does not hardcode any project-specific environment names.

## Example submodules

Two optional submodules demonstrate the patterns - enable whichever you want to study, then model your own steps on them.

`deploy_steps_example` ships a single `RecordEnvironment` step that records the current environment to State on every deploy. It is the minimal, safe demonstration of the pattern: dependency injection via `create()`, the `environment()` helper, and an idempotent `run()`.

`deploy_steps_example_advanced` demonstrates real-world deploy work. Each step gates itself out when its prerequisite is missing, so enabling the module never breaks a deploy on its own:

- `ImportMigrations` redispatches `migrate:import --all --update` (gated on the `migrate_tools` module).
- `ReindexSearchApi` redispatches `search-api:index` (gated on the `search_api` module).
- `RunExternalScript` runs an external program via Symfony's `Process` (gated unless `$settings['deploy_steps_example_script']` points at an existing file).

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
3. Run `ahoy build`

## Building website

`ahoy build` assembles the codebase, starts the PHP server and provisions the
Drupal website with this extension enabled. These operations are executed using
scripts within [`.devtools`](.devtools) directory. CI uses the same scripts to
build and test this extension.

The resulting codebase is then placed in the `build` directory. The extension
files are symlinked into the Drupal site structure.

The `build` command is a wrapper for more granular commands:
```bash
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
variable before running the `ahoy build` command:

```bash
DRUPAL_VERSION=11 ahoy build        # Drupal 11
DRUPAL_VERSION=11@alpha ahoy build  # Drupal 11 alpha
DRUPAL_VERSION=10@beta ahoy build   # Drupal 10 beta
DRUPAL_VERSION=11.1 ahoy build      # Drupal 11.1
```

The `minimum-stability` setting in the `composer.json` file is
automatically adjusted to match the specified Drupal version's stability.

### Patching dependencies

To apply patches to the dependencies, add a patch to the `patches` section of
`composer.json`. Local patches are sourced from the `patches` directory.

### Providing `GITHUB_TOKEN`

To overcome GitHub API rate limits, you may provide a `GITHUB_TOKEN` environment
variable with a personal access token.

### Provisioning the website

The `provision` command installs the Drupal website from the `standard`
profile with the extension (and any `suggest`'ed extensions) enabled. The
profile can be changed by setting the `DRUPAL_PROFILE` environment variable.

The website will be available at http://localhost:8000 by default. The
hostname can be changed by setting the `WEBSERVER_HOST` environment variable.

The `WEBSERVER_PORT` is resolved with the following precedence:

1. **`WEBSERVER_PORT` exported in the shell** - used as-is. Useful for one-off
   runs: `WEBSERVER_PORT=9000 ahoy build`.
2. **`WEBSERVER_PORT` line in the project-root `.env` file** - used as-is.
   The `start` script does not modify `.env` when this entry is already
   present, so the same port is reused across `start`, `stop`, `provision`,
   `drush` and `login` commands.
3. **Neither is set** - the `start` script discovers the first free port in
   the range `8000-8099` and writes it to `.env` as `WEBSERVER_PORT=NNNN`.
   Subsequent commands read this value from `.env`.

To force re-discovery, delete `.env` (or just the `WEBSERVER_PORT` line in
it) and re-run `ahoy start`.

An SQLite database is created in `/tmp/site_deploy_steps.sqlite` file.
You can browse the contents of the created SQLite database using
[DB Browser for SQLite](https://sqlitebrowser.org/).

A one-time login link will be printed to the console.

### Step-debugging with XDebug

PHP step-debugging is supported via [XDebug](https://xdebug.org/docs/install). Install the XDebug PHP extension on your host (`php -v` should mention `with Xdebug`), then toggle it on the development server:

```bash
ahoy debug      # restart with XDebug enabled (aliases: debug-on, xdebug, xdebug-on)

ahoy start      # restart without XDebug (aliases: debug-off, xdebug-off)
```

The `debug` command probes the running PHP server's command line for `xdebug.mode=debug` and skips the restart if XDebug is already enabled. Code coverage stays on [pcov](https://github.com/krakjoe/pcov) because `xdebug.mode=debug` does not include `coverage`.

To start and stop debug sessions from the browser, install the Xdebug Helper extension: [Chrome](https://chromewebstore.google.com/detail/xdebug-helper-by-jetbrain/aoelhdemabeimdhedkidlnbkfhnhgnhm) / [Firefox](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-by-jetbrains/).

## Coding standards

The `ahoy lint` command checks the codebase using multiple tools:
- PHP code standards checking against `Drupal` and `DrupalPractice` standards.
- PHP code static analysis with PHPStan.
- PHP deprecated code analysis and auto-fixing with Drupal Rector.
- Twig code analysis with Twig CS Fixer.

The configuration files for these tools are located in the root of the codebase.

### Fixing coding standards issues

To fix coding standards issues automatically, run `ahoy lint-fix`. This runs the
same tools as `lint` command but with the `--fix` option (for the tools that
support it).

## Testing

The `ahoy test` command runs the tests for this extension.

The tests are located in the `tests/src` directory. The `phpunit.xml` file
configures PHPUnit to run the tests. It uses Drupal core's bootstrap file
`core/tests/bootstrap.php` to bootstrap the Drupal environment before running
the tests.

The `test` command is a wrapper for multiple test commands:
```bash
ahoy test-unit                    # Run Unit tests
ahoy test-kernel                  # Run Kernel tests
ahoy test-functional              # Run Functional tests
```

### Running specific tests

You can run specific tests by passing a path to the test file or PHPUnit CLI
option (`--filter`, `--group`, etc.) to the `ahoy test` command:

```bash
ahoy test-unit tests/src/Unit/MyUnitTest.php
ahoy test-unit -- --group=wip
```

---
_This repository was created using the [Drupal Extension Scaffold](https://github.com/AlexSkrypnyk/drupal_extension_scaffold) project template_
