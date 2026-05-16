<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Tests;

use ContextualWP\Helpers\Utilities;
use ContextualWP\HousebuilderPack\Services\PlotDatasetMapper;
use PHPUnit\Framework\TestCase;

/**
 * last_modified_by exposes only the safe modified-author display label from ContextualWP core.
 */
final class PlotDatasetMapperLastModifiedByTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		Utilities::reset();
	}

	public function testResolveLastModifiedByLabelReturnsTrimmedDisplayName(): void
	{
		Utilities::$mockReturn = '  Jane Editor  ';

		$post = $this->plotPost(101);

		$this->assertSame('Jane Editor', PlotDatasetMapper::resolveLastModifiedByLabel($post));
		$this->assertSame($post, Utilities::$lastPostArg);
	}

	public function testResolveLastModifiedByLabelReturnsNullWhenCoreReturnsNull(): void
	{
		Utilities::$mockReturn = null;

		$this->assertNull(PlotDatasetMapper::resolveLastModifiedByLabel($this->plotPost(102)));
	}

	public function testResolveLastModifiedByLabelReturnsNullWhenCoreReturnsEmptyString(): void
	{
		Utilities::$mockReturn = '   ';

		$this->assertNull(PlotDatasetMapper::resolveLastModifiedByLabel($this->plotPost(103)));
	}

	public function testMapPostIncludesLastModifiedByWithSafeDisplayLabelOnly(): void
	{
		Utilities::$mockReturn = 'Sam Editor';

		$mapped = PlotDatasetMapper::mapPost($this->plotPost(42), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertArrayHasKey('last_modified_by', $mapped);
		$this->assertSame('Sam Editor', $mapped['last_modified_by']);
		$this->assertIsString($mapped['last_modified_by']);
	}

	public function testMapPostSetsLastModifiedByNullWhenNoSafeLabel(): void
	{
		Utilities::$mockReturn = null;

		$mapped = PlotDatasetMapper::mapPost($this->plotPost(43), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertArrayHasKey('last_modified_by', $mapped);
		$this->assertNull($mapped['last_modified_by']);
	}

	private function plotPost(int $id): \WP_Post
	{
		$post = new \WP_Post();
		$post->ID = $id;
		$post->post_type = 'plot';
		$post->post_status = 'publish';
		$post->post_modified_gmt = '2026-01-15 12:00:00';

		return $post;
	}

}
