# Kirby GitHub Actions

GitHub Actions for the CI of Kirby's core, libraries and plugins.

## Usage

Each action is a step, in one job or spread over several:

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

Pin each action to a commit with its version as comment.

## Actions

The PHP actions that check the code each run one composer script of the project:

```json
"scripts": {
	"analyze:composer": [
		"composer validate --strict --no-check-version --no-check-all",
		"cpx icanhazstring/composer-unused:0.9.6 --no-progress"
	],
	"analyze:psalm": "cpx vimeo/psalm:6.16.1",
	"fix": "cpx getkirby/php-cs:0.2.1 fix",
	"test": "cpx phpunit/phpunit:12.5.34"
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

Sets up PHP with cached extensions, [cpx](https://github.com/RedExplosion/cpx) for the tools in the composer scripts, and the Composer dependencies.

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

## Tests

CI runs the actions on `test-project`.
