# Kirby GitHub Actions

Reusable workflows and GitHub Actions for the CI of Kirby's core, libraries and plugins, from PHP 8.3 on.

## Usage

The whole CI of a project:

```yaml
jobs:
  php:
    uses: getkirby/actions/.github/workflows/php.yml@<sha> # <version>
    secrets: inherit

  node:
    uses: getkirby/actions/.github/workflows/node.yml@<sha> # <version>
    secrets: inherit
```

Or the actions as single steps, in one job or spread over several:

```yaml
jobs:
  analysis:
    runs-on: ubuntu-24.04

    steps:
      - name: Checkout
        uses: getkirby/actions/checkout@<sha> # <version>

      - name: Setup PHP
        uses: getkirby/actions/php@<sha> # <version>

      - name: Analyze PHP
        uses: getkirby/actions/psalm@<sha> # <version>
```

Pin each action to a commit with its version as comment, see [Conventions](#conventions).

## Workflows

### php.yml

Tests with the phpunit action on every PHP version that `composer.json` allows, and with a `composer.lock` once more with the latest dependencies. Checks with the composer, psalm and php-cs actions on the lowest PHP version. Each step only runs if the project has its composer script, and a `CODECOV_TOKEN` secret uploads the coverage.

| Input                | Default | Purpose                                                                                                    |
| -------------------- | ------- | ---------------------------------------------------------------------------------------------------------- |
| `apt`                |         | System packages for the tests, e.g. `imagemagick locales-all`                                              |
| `codecov-flags`      |         | Codecov flags, e.g. `backend`                                                                              |
| `composer-normalize` | `false` | Check that `composer.json` is normalized, with composer-normalize                                          |
| `env`                |         | Environment variables for the tests, as `KEY=value` lines                                                  |
| `extensions`         |         | PHP extensions, e.g. `mbstring, gd`                                                                        |
| `ini`                |         | php.ini values for the tests, e.g. `apc.enable_cli=1`                                                      |
| `phpunit-args`       |         | Arguments for the test script, e.g. `--display-skipped`                                                    |
| `sarif`              | `false` | Upload the Psalm results to GitHub code scanning (the calling job needs `security-events: write`)          |
| `services`           |         | Services for the tests, `redis` and/or `memcached`                                                         |
| `suggestions`        | `false` | Post the PHP-CS-Fixer fixes as suggestions on pull requests (the calling job needs `pull-requests: write`) |
| `upstream`           |         | Only run in this original repository and in forks outside its owner, e.g. `getkirby/kirby`                 |

### node.yml

Sets up Node with the node action and runs the npm scripts `lint`, `format`, `types`, `test` (with the vitest action) and `build`, each only if the project has it. A `CODECOV_TOKEN` secret uploads the coverage.

```json
"scripts": {
	"build": "kirbyup src/index.js",
	"format": "prettier --check .",
	"format:fix": "prettier --write .",
	"lint": "eslint . --max-warnings 0",
	"lint:fix": "eslint . --fix",
	"test": "vitest run",
	"types": "tsc --noEmit"
}
```

`lint`, `format` and `types` only check, their `:fix` variants write, so CI runs the same scripts as you do locally. `@vitest/coverage-v8` adds the coverage.

| Input               | Default | Purpose                                                                                    |
| ------------------- | ------- | ------------------------------------------------------------------------------------------ |
| `codecov-flags`     |         | Codecov flags, e.g. `frontend`                                                             |
| `upstream`          |         | Only run in this original repository and in forks outside its owner, e.g. `getkirby/kirby` |
| `working-directory` | `.`     | Directory of the `package.json`                                                            |

### php-versions.yml

Runs the php-versions action as a job, for a matrix over all PHP versions and the jobs that need the lowest one:

```yaml
jobs:
  versions:
    uses: getkirby/actions/.github/workflows/php-versions.yml@<sha> # <version>

  tests:
    needs: versions
    strategy:
      matrix:
        php: ${{ fromJSON(needs.versions.outputs.versions) }}
```

## Actions

The PHP actions that check the code each run one composer script of the project:

```json
"scripts": {
	"analyze:composer": [
		"composer validate --strict --no-check-version --no-check-all",
		"cpx icanhazstring/composer-unused:0.9.7 --no-progress"
	],
	"analyze:psalm": "cpx vimeo/psalm:6.20.4",
	"fix": "cpx getkirby/php-cs:0.2.1 fix",
	"test": "cpx phpunit/phpunit:12.5.42"
}
```

### apt

Installs system packages with apt and caches them by their resolved versions, as the runner's apt mirror is often slow.

| Input      | Default | Purpose                                  |
| ---------- | ------- | ---------------------------------------- |
| `packages` |         | Packages, e.g. `imagemagick locales-all` |

### checkout

Checks out the repository with its last two commits, which Codecov needs for pull requests, and without keeping the token in the git config.

| Input                 | Default | Purpose                                                                |
| --------------------- | ------- | ---------------------------------------------------------------------- |
| `ref`                 |         | Branch, tag or commit, defaults to the one that triggered the workflow |
| `persist-credentials` | `false` | Keep the token in the git config, for jobs that push                   |

### composer

Runs `analyze:composer`, and checks that `composer.json` is normalized if the php action installed composer-normalize through `tools`.

### node

Sets up Node and installs the npm dependencies with `npm ci`, which a cached `node_modules` skips.

| Input               | Default | Purpose                                                         |
| ------------------- | ------- | --------------------------------------------------------------- |
| `version`           |         | Node version, defaults to the lowest that `package.json` allows |
| `working-directory` | `.`     | Directory of the `package.json`                                 |

### node-versions

Finds the Node versions that `engines.node` in `package.json` allows, with the runner's own Node, so it can come before the node action.

| Input               | Default | Purpose                         |
| ------------------- | ------- | ------------------------------- |
| `working-directory` | `.`     | Directory of the `package.json` |

| Output     | Purpose                                            |
| ---------- | -------------------------------------------------- |
| `versions` | The Node versions as JSON list, e.g. `["22","24"]` |
| `lowest`   | The lowest of them                                 |

### php

Sets up PHP with cached extensions, [cpx](https://github.com/laravel/cpx) for the tools in the composer scripts, and the Composer dependencies.

| Input          | Default  | Purpose                                                                                            |
| -------------- | -------- | -------------------------------------------------------------------------------------------------- |
| `version`      |          | PHP version, defaults to the lowest that `composer.json` allows                                    |
| `extensions`   |          | PHP extensions, e.g. `mbstring, gd`                                                                |
| `ini`          |          | php.ini values                                                                                     |
| `coverage`     | `none`   | `pcov` or `xdebug`                                                                                 |
| `tools`        |          | Tools for [setup-php](https://github.com/shivammathur/setup-php), e.g. `composer-normalize:2.54.0` |
| `dependencies` | `locked` | `locked` (from `composer.lock`), `latest`, `lowest` or `none` (for a committed `vendor`)           |

### php-cs

Runs `fix -- --diff --dry-run`.

| Input         | Default | Purpose                                                                                                     |
| ------------- | ------- | ----------------------------------------------------------------------------------------------------------- |
| `suggestions` | `false` | Post the fixes as suggestions on pull requests (needs `pull-requests: write`; not for forks and Dependabot) |

### php-versions

Finds the PHP versions that `composer.json` allows, with the runner's own PHP, so it can come before the php action.

| Output     | Purpose                                             |
| ---------- | --------------------------------------------------- |
| `versions` | The PHP versions as JSON list, e.g. `["8.3","8.4"]` |
| `lowest`   | The lowest of them                                  |

### phpunit

Checks the Composer platform requirements and runs `test`, with the PHPUnit results cached in `.phpunit.cache`. With a coverage driver (`coverage: pcov` on the php action), the tests write a Clover report, which is uploaded to Codecov if a token is given.

| Input           | Default | Purpose                                             |
| --------------- | ------- | --------------------------------------------------- |
| `args`          |         | Arguments for the test script, e.g. `--processes 4` |
| `codecov-token` |         | Codecov token, e.g. `${{ secrets.CODECOV_TOKEN }}`  |
| `codecov-flags` |         | Codecov flags, e.g. `backend`                       |

### psalm

Runs `analyze:psalm` with GitHub annotations. Psalm checks the built-in functions against the PHP that runs it, so run it on the lowest PHP version, which is the php action's default.

| Input   | Default | Purpose                                                                     |
| ------- | ------- | --------------------------------------------------------------------------- |
| `sarif` | `false` | Upload the results to GitHub code scanning (needs `security-events: write`) |

### vitest

Runs `test` with Vitest. If `@vitest/coverage-v8` is installed, the tests write a Clover report, which is uploaded to Codecov if a token is given.

| Input               | Default | Purpose                                            |
| ------------------- | ------- | -------------------------------------------------- |
| `working-directory` | `.`     | Directory of the `package.json`                    |
| `codecov-token`     |         | Codecov token, e.g. `${{ secrets.CODECOV_TOKEN }}` |
| `codecov-flags`     |         | Codecov flags, e.g. `frontend`                     |

## Conventions

Each workflow sets these itself:

```yaml
on:
  # long-lived branches only, so a pull request doesn't run twice
  # and restores the caches of its base branch
  push:
    branches: [main]
  pull_request:

# a new push cancels the runs it supersedes; the prefix is fixed,
# as in a called workflow github.workflow is the caller's name
concurrency:
  group: ci-${{ github.ref }}
  cancel-in-progress: true

# a job that needs more asks for it
permissions:
  contents: read

jobs:
  tests:
    # bumped on purpose like any pin; repeated values as anchors
    runs-on: &runner ubuntu-24.04
    # GitHub's default is six hours
    timeout-minutes: &timeout 10
    strategy:
      # one failing PHP version doesn't cancel the others
      fail-fast: false

    steps:
      # every action pinned to a commit, with its version as comment
      - uses: getkirby/actions/checkout@<sha> # <version>

  analysis:
    runs-on: *runner
    timeout-minutes: *timeout
```

And each repository:

- **Versions:** PHP and Node versions only in `composer.json` (`require.php`) and `package.json` (`engines.node`).
- **Dependencies:** caret ranges with the locked versions as lower bound, so a release stays installable when a dependency gets a security advisory. CI tests the locked and the latest versions.
- **Tools:** exact versions in the composer scripts, run through cpx, so every repository formats and analyzes alike. composer-normalize, which cpx can't run, is the exception. npm tools are pinned by the committed `package-lock.json`.
- **Updates:** by hand, to versions that are at least two weeks old:
  ```sh
  # GitHub Actions, with --recursive in this repository
  npx actions-up --min-age 14

  # npm
  npx npm-check-updates --interactive --cooldown 14

  # Composer
  composer outdated --direct --cooldown-period="14 days"
  composer update && composer bump

  # cpx tools, with --update to apply
  cpx getkirby/actions outdated --min-age 14
  ```

## Tests

CI runs the actions and `kirby-actions outdated` on `test-project`.
