# composer-require-patch-enforcer

A [Composer plugin](https://getcomposer.org/doc/articles/plugins.md) that blocks `composer require` unless each package uses an **exact three-part version** only: `major.minor.patch` with **no range operators** (`^`, `~`, `>=`, `*`, `||`, spaces for compound constraints, etc.). Example: `monolog/monolog:3.5.0`.

This pushes root requirements toward explicit pins to reduce accidental wide constraints as part of supply-chain hygiene.

## Requirements

- PHP `>= 8.1`
- Composer with plugin API `^2.3` (Composer 2.3+)

## Installation

### From Packagist (recommended)

After the package is registered on [Packagist](https://packagist.org/), install a **stable release with an exact version** (this matches how this plugin expects root requirements to be written once it is active):

```bash
composer require tsmsogn/composer-require-patch-enforcer:1.0.0
composer config allow-plugins.tsmsogn/composer-require-patch-enforcer true
```

The **first** time you add this package, the plugin is not in `vendor` yet, so Composer will also accept a range (e.g. `^1.0`) for that single command. After the plugin is installed, further `composer require` calls must use **exact** `x.y.z` for every package on the command line (or use `COMPOSER_REQUIRE_PATCH_ENFORCER_SKIP=1` / `--no-plugins`). Upgrading this plugin to `1.0.1` should look like: `composer require tsmsogn/composer-require-patch-enforcer:1.0.1`.

### Development: VCS or path

From Git (no Packagist):

```bash
composer config repositories.composer-require-patch-enforcer vcs https://github.com/tsmsogn/composer-require-patch-enforcer.git
composer require tsmsogn/composer-require-patch-enforcer:@dev
```

From a local path:

```bash
composer config repositories.composer-require-patch-enforcer path ../composer-require-patch-enforcer
composer require tsmsogn/composer-require-patch-enforcer:@dev
```

If the package is not on Packagist, you may need `minimum-stability` or a stability suffix (e.g. `@dev`) as above.

### Allow the plugin

Composer 2.2+ requires you to opt in to plugins:

```bash
composer config allow-plugins.tsmsogn/composer-require-patch-enforcer true
```

## Usage

**Every package on the command line** must include a version that matches this pattern (whole string, after trim):

- Optional leading `v`
- Three numeric segments: `digits.digits.digits`
- Nothing else (no stability suffix like `@stable`, no pre-release like `-alpha`)

Regex: `^v?\d+\.\d+\.\d+$`

### Accepted examples

```bash
composer require monolog/monolog:3.5.0
composer require monolog/monolog:v3.5.0
composer require "vendor/package 1.0.0"
```

### Rejected examples

```bash
composer require monolog/monolog
composer require monolog/monolog:^3.5.0
composer require monolog/monolog:~3.5.0
composer require "vendor/package >=1.0.0 <2.0.0"
composer require monolog/monolog:^3.5
composer require monolog/monolog:3.5.*
composer require monolog/monolog:3.5.0@stable
```

These fail because the version is missing, or it is not a single exact `x.y.z` token.

### Interactive `composer require` with no arguments

If you run `composer require` **without** any package arguments, the plugin does **not** validate (Composer’s interactive wizard may still resolve versions for you). For strict enforcement in CI, use non-interactive mode and always pass explicit `package:version` arguments.

## Disabling enforcement

**Temporary (one command):**

```bash
COMPOSER_REQUIRE_PATCH_ENFORCER_SKIP=1 composer require some/package:^1.0.0
```

**Skip plugins for that invocation:**

```bash
composer require some/package --no-plugins
```

**Permanent:** remove the package from `composer.json` or set `allow-plugins` for this package to `false` and avoid loading it.

## Limitations

- The constraint in `composer.json` will be that exact string; **`composer update` can still move to a newer version only if you change the constraint** — unlike `^`, a plain `3.5.0` in `require` typically means “== that version” for resolution. Transitive dependencies are not controlled by this plugin.
- Help output is not blocked: `composer require -h` / `--help` is ignored by the plugin.

## License

MIT
