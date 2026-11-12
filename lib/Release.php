<?php

namespace Kirby\Actions;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use RuntimeException;
use UnexpectedValueException;

/**
 * A release of a package on Packagist
 */
class Release
{
	protected static array $cache = [];

	public function __construct(
		public readonly string $version,
		public readonly Php $php,
		public readonly int $time
	) {
	}

	/**
	 * The data of each release of a package on Packagist
	 */
	protected static function data(string $package): array
	{
		$url     = 'https://repo.packagist.org/p2/' . $package . '.json';
		$context = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'getkirby/actions']]);
		$json    = @file_get_contents($url, false, $context);

		if ($json === false) {
			throw new RuntimeException('Packagist has no ' . $package);
		}

		$minified = json_decode($json, true)['packages'][$package] ?? [];
		$releases = [];
		$release  = [];

		// each release only lists what changed from the one before
		foreach ($minified as $changes) {
			$release    = [...$release, ...$changes];
			$release    = array_filter($release, fn ($value) => $value !== '__unset');
			$releases[] = $release;
		}

		return $releases;
	}

	/**
	 * Creates a release from its data
	 */
	public static function factory(array $props): static
	{
		return new static(
			version: ltrim($props['version'], 'v'),
			php: new Php($props['require']['php'] ?? '*'),
			time: strtotime($props['time'] ?? 'now')
		);
	}

	/**
	 * Whether the release is the given version
	 */
	public function is(string $version): bool
	{
		return Comparator::equalTo($this->version, ltrim($version, 'v'));
	}

	/**
	 * Whether the release has the same major as the given
	 * version and isn't older, e.g. 1.4.0 for 1.2.3
	 */
	public function isCompatibleWith(string $version): bool
	{
		try {
			return Semver::satisfies($this->version, '^' . $version);
		} catch (UnexpectedValueException) {
			// an old release with a version Composer no longer reads
			return false;
		}
	}

	/**
	 * Whether the release is newer than another one
	 */
	public function isNewerThan(Release $release): bool
	{
		return Comparator::greaterThan($this->version, $release->version);
	}

	/**
	 * Whether the release is at least this many days old
	 */
	public function isOlderThan(int $days): bool
	{
		return $this->time <= time() - $days * 86400;
	}

	/**
	 * Whether the release is stable, not e.g. a beta
	 */
	public function isStable(): bool
	{
		return VersionParser::parseStability($this->version) === 'stable';
	}

	/**
	 * The stable releases of a package
	 *
	 * @return array<static>
	 */
	public static function load(string $package): array
	{
		$package = strtolower($package);

		if (isset(static::$cache[$package]) === true) {
			return static::$cache[$package];
		}

		$releases = [];

		foreach (static::data($package) as $props) {
			$release = static::factory($props);

			if ($release->isStable() === true) {
				$releases[] = $release;
			}
		}

		return static::$cache[$package] = $releases;
	}
}
