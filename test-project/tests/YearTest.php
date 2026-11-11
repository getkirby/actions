<?php

namespace Kirby\Actions;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[CoversClass(Year::class)]
final class YearTest extends TestCase
{
	public function testCurrent(): void
	{
		$clock = new class () implements ClockInterface {
			public function now(): DateTimeImmutable
			{
				return new DateTimeImmutable('2026-11-11');
			}
		};

		$this->assertSame(2026, (new Year($clock))->current());
	}
}
