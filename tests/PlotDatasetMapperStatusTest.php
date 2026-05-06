<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Tests;

use ContextualWP\HousebuilderPack\Services\PlotDatasetMapper;
use PHPUnit\Framework\TestCase;

/**
 * Plot status is read from the plot post’s meta only (not from linked development/house-type posts).
 * These cases mirror ACF-style choice storage that previously produced null after normalisation.
 */
final class PlotDatasetMapperStatusTest extends TestCase
{
	public function testPlainStringNormalisedToLowercase(): void
	{
		$this->assertSame('sold', PlotDatasetMapper::normalizePlotStatusMetaValue('Sold'));
	}

	public function testAcfStyleValueKey(): void
	{
		$this->assertSame('sold', PlotDatasetMapper::normalizePlotStatusMetaValue(['value' => 'sold']));
	}

	public function testAcfStyleLabelFallbackWhenValueEmpty(): void
	{
		$this->assertSame('sold', PlotDatasetMapper::normalizePlotStatusMetaValue(['value' => '', 'label' => 'Sold']));
	}

	public function testSingleElementStringList(): void
	{
		$this->assertSame('reserved', PlotDatasetMapper::normalizePlotStatusMetaValue(['reserved']));
	}

	public function testNumericStringPreserved(): void
	{
		$this->assertSame('3', PlotDatasetMapper::normalizePlotStatusMetaValue(3));
	}

	public function testUnrecognisedArrayShapeReturnsNull(): void
	{
		$this->assertNull(PlotDatasetMapper::normalizePlotStatusMetaValue(['a' => 1, 'b' => 2]));
	}
}
