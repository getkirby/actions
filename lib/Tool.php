<?php

namespace Kirby\Actions;

/**
 * A tool that the composer scripts run through cpx with a version,
 * e.g. `cpx vendor/name:1.2.3` or `cpx "vendor/name:1.2.3||2.0.1"`
 */
class Tool
{
	// `cpx vendor/name:version`, also in quotes
	protected const PATTERN = '!cpx\s+"?([a-z0-9_.-]+/[a-z0-9_.-]+):([^\s"]+)!i';

	public function __construct(
		public readonly string $package,
		public readonly string $pin
	) {
	}

	/**
	 * The commands of the composer scripts, which
	 * can each be a single command or a list of them
	 */
	protected static function commands(array $composer): array
	{
		$commands = [];

		foreach ($composer['scripts'] ?? [] as $script) {
			$commands = [...$commands, ...(array)$script];
		}

		return $commands;
	}

	/**
	 * The tools in the composer scripts of a composer.json
	 *
	 * @return array<static>
	 */
	public static function load(array $composer): array
	{
		$tools = [];

		foreach (static::commands($composer) as $command) {
			preg_match_all(static::PATTERN, $command, $matches, PREG_SET_ORDER);

			foreach ($matches as [, $package, $pin]) {
				$tools[$package . ':' . $pin] = new static($package, $pin);
			}
		}

		return array_values($tools);
	}

	/**
	 * The newest release that is old enough and still runs on every PHP version
	 * (out of the given ones) that the pinned version runs on; within the
	 * major of the pinned version, unless a new major is allowed
	 */
	public function newest(
		string $pinned,
		array $versions,
		bool $major = false,
		int $minAge = 0
	): Release|null {
		// the PHP versions that an update has to keep running on
		$runs   = $this->release($pinned)?->php->versions($versions) ?? [];
		$newest = null;

		foreach ($this->releases() as $release) {
			if ($release->isOlderThan($minAge) === false) {
				continue;
			}

			if ($major === false && $release->isCompatibleWith($pinned) === false) {
				continue;
			}

			if (array_diff($runs, $release->php->versions($versions)) !== []) {
				continue;
			}

			if ($newest === null || $release->isNewerThan($newest) === true) {
				$newest = $release;
			}
		}

		return $newest;
	}

	/**
	 * The release of a pinned version
	 */
	public function release(string $version): Release|null
	{
		foreach ($this->releases() as $release) {
			if ($release->is($version) === true) {
				return $release;
			}
		}

		return null;
	}

	/**
	 * The stable releases of the tool on Packagist
	 *
	 * @return array<Release>
	 */
	public function releases(): array
	{
		return Release::load($this->package);
	}

	/**
	 * The pinned versions, e.g. ['1.2.3', '2.0.1']
	 */
	public function versions(): array
	{
		return explode('||', $this->pin);
	}
}
