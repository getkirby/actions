<?php

namespace Kirby\Actions;

use Psr\Clock\ClockInterface;

final class Year
{
	public function __construct(
		private ClockInterface $clock
	) {
	}

	public function current(): int
	{
		return (int)$this->clock->now()->format('Y');
	}
}
