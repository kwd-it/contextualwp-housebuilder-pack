<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Tests;

use ContextualWP\HousebuilderPack\Services\CompletenessSignals;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for generic asset requirement defaults in completeness signals.
 */
final class CompletenessSignalsTest extends TestCase
{
	public function testPlotStatusRequiresFloorPlanForMarketingReadyStatuses(): void
	{
		$this->assertTrue(CompletenessSignals::plotStatusExpectsFloorPlan('available'));
		$this->assertTrue(CompletenessSignals::plotStatusExpectsFloorPlan('released'));
		$this->assertTrue(CompletenessSignals::plotStatusExpectsFloorPlan('for-sale'));
		$this->assertTrue(CompletenessSignals::plotStatusExpectsFloorPlan('coming soon'));
	}

	public function testPlotStatusExcludesFloorPlanForSoldOrReserved(): void
	{
		$this->assertFalse(CompletenessSignals::plotStatusExpectsFloorPlan('sold'));
		$this->assertFalse(CompletenessSignals::plotStatusExpectsFloorPlan('reserved'));
		$this->assertFalse(CompletenessSignals::plotStatusExpectsFloorPlan('under offer'));
	}

	public function testPlotStatusFloorPlanRequirementUndeterminedWhenStatusUnrecognised(): void
	{
		$this->assertNull(CompletenessSignals::plotStatusExpectsFloorPlan(null));
		$this->assertNull(CompletenessSignals::plotStatusExpectsFloorPlan('pipeline'));
	}
}
