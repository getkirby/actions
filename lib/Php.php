<?php

namespace Kirby\Actions;

use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\VersionParser;
use UnexpectedValueException;

/**
 * A PHP version constraint, e.g. from the `require`
 * of a composer.json, and the versions that the actions test
 */
class Php
{
	public const VERSIONS = ['8.3', '8.4', '8.5', '8.6'];

	public function __construct(
		public readonly string $constraint
	) {
	}

	/**
	 * Whether the actions support the constraint, which they
	 * don't if it also allows versions below the ones they test
	 */
	public function isSupported(): bool
	{
		$constraint = $this->parse();

		if ($constraint === null) {
			return false;
		}

		$older = $this->parser()->parseConstraints('<' . static::VERSIONS[0]);
		return $constraint->matches($older) === false;
	}

	/**
	 * The parsed constraint, or null if Composer can't read it
	 */
	protected function parse(): ConstraintInterface|null
	{
		try {
			return $this->parser()->parseConstraints($this->constraint);
		} catch (UnexpectedValueException) {
			return null;
		}
	}

	protected function parser(): VersionParser
	{
		return new VersionParser();
	}

	/**
	 * The versions that the constraint allows,
	 * e.g. ['8.3', '8.4'] for '~8.3.0 || ~8.4.0'
	 */
	public function versions(array $versions = self::VERSIONS): array
	{
		$constraint = $this->parse();

		if ($constraint === null) {
			return [];
		}

		$allowed = [];

		foreach ($versions as $version) {
			$minor = $this->parser()->parseConstraints('~' . $version . '.0');

			if ($constraint->matches($minor) === true) {
				$allowed[] = $version;
			}
		}

		return $allowed;
	}
}
