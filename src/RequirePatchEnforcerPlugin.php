<?php

declare(strict_types=1);

namespace Tsmsogn\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Version\VersionParser;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreCommandRunEvent;
use Composer\Util\Platform;

/**
 * Subscribes to PluginEvents::PRE_COMMAND_RUN and allows only exact x.y.z constraints for the require command (no ^, ~, >=, etc.).
 */
final class RequirePatchEnforcerPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::PRE_COMMAND_RUN => 'onPreCommandRun',
        ];
    }

    public function onPreCommandRun(PreCommandRunEvent $event): void
    {
        if ($event->getCommand() !== 'require') {
            return;
        }

        $input = $event->getInput();
        if ($input->hasParameterOption(['--help', '-h'], true)) {
            return;
        }

        if (Platform::getEnv('COMPOSER_REQUIRE_PATCH_ENFORCER_SKIP') === '1') {
            return;
        }

        /** @var list<string> $packages */
        $packages = $input->getArgument('packages') ?? [];
        if ($packages === []) {
            return;
        }

        $parser = new VersionParser();
        $pairs = $parser->parseNameVersionPairs($packages);

        $errors = [];
        foreach ($pairs as $pair) {
            $name = $pair['name'];
            if (!isset($pair['version'])) {
                $errors[] = sprintf(
                    'Package "%s" has no version. Example: %s:1.2.3 (range operators like ^, ~, >= are not allowed).',
                    $name,
                    $name
                );

                continue;
            }

            $constraint = (string) $pair['version'];
            if ($constraint === 'guess') {
                $errors[] = sprintf(
                    'Package "%s" uses guessed constraint mode. Specify an exact version only (e.g. %s:1.2.3).',
                    $name,
                    $name
                );

                continue;
            }

            if (!self::isExactThreePartVersion($constraint)) {
                $errors[] = sprintf(
                    'Package "%s" has invalid constraint "%s". Only plain x.y.z is allowed (e.g. %s:3.5.0).',
                    $name,
                    $constraint,
                    $name
                );
            }
        }

        if ($errors === []) {
            return;
        }

        $message = "[composer-require-patch-enforcer] Aborted composer require.\n"
            . "Only exact x.y.z versions are allowed (e.g. 3.5.0), with no range operators.\n\n"
            . implode("\n", $errors)
            . "\n\nTo bypass: COMPOSER_REQUIRE_PATCH_ENFORCER_SKIP=1 composer require ...\n"
            . 'To skip plugins: composer require ... --no-plugins';

        throw new \RuntimeException($message);
    }

    /**
     * True if the trimmed string matches optional "v" plus major.minor.patch only.
     */
    private static function isExactThreePartVersion(string $constraint): bool
    {
        $constraint = trim($constraint);
        if ($constraint === '') {
            return false;
        }

        return (bool) preg_match('/^v?\d+\.\d+\.\d+$/D', $constraint);
    }
}
