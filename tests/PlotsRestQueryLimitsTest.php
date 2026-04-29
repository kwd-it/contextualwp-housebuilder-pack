<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Tests;

use ContextualWP\HousebuilderPack\Services\PlotsRestQueryLimits;
use PHPUnit\Framework\TestCase;

final class PlotsRestQueryLimitsTest extends TestCase
{
	public function testDefaultUsesSanitisedPerPageFallbackWhenNoPaginationQueryKeys(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(1, 500, []);

		$this->assertSame(1, $r['page']);
		$this->assertSame(500, $r['per_page']);
	}

	public function testDefaultRespectsFallbackWhenQueryHasUnrelatedKeys(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(1, 500, ['foo' => 'bar']);

		$this->assertSame(500, $r['per_page']);
	}

	public function testLimitThreeReturnsThree(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(1, 500, ['limit' => '3']);

		$this->assertSame(3, $r['per_page']);
	}

	public function testPerPageInQueryTakesPrecedenceOverLimit(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(1, 500, ['limit' => '50', 'per_page' => '2']);

		$this->assertSame(2, $r['per_page']);
	}

	public function testInvalidLimitFallsBackToDefault(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(1, 500, ['limit' => 'not-a-number']);

		$this->assertSame(PlotsRestQueryLimits::DEFAULT_PER_PAGE, $r['per_page']);
	}

	public function testEmptyLimitFallsBackToDefault(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(1, 500, ['limit' => '']);

		$this->assertSame(PlotsRestQueryLimits::DEFAULT_PER_PAGE, $r['per_page']);
	}

	public function testLimitCappedAtMaximum(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(1, 500, ['limit' => '9999']);

		$this->assertSame(PlotsRestQueryLimits::MAX_PER_PAGE, $r['per_page']);
	}

	public function testLimitLessThanOneClampsToOne(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(1, 500, ['limit' => '0']);

		$this->assertSame(1, $r['per_page']);
	}

	public function testPageMinimumOne(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(0, 500, ['limit' => '5']);

		$this->assertSame(1, $r['page']);
		$this->assertSame(5, $r['per_page']);
	}

	public function testExplicitPerPageInQueryStillWorks(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(2, 500, ['per_page' => '10']);

		$this->assertSame(2, $r['page']);
		$this->assertSame(10, $r['per_page']);
	}

	public function testInvalidExplicitPerPageFallsBackToDefault(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(1, 500, ['per_page' => 'x']);

		$this->assertSame(PlotsRestQueryLimits::DEFAULT_PER_PAGE, $r['per_page']);
	}

	public function testFirstValueUsedWhenQueryParamIsArray(): void
	{
		$r = PlotsRestQueryLimits::resolvePageAndPerPage(1, 500, ['limit' => ['3', '9']]);

		$this->assertSame(3, $r['per_page']);
	}
}
