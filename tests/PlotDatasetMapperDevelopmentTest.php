<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Tests;

use ContextualWP\HousebuilderPack\Services\PlotDatasetMapper;
use PHPUnit\Framework\TestCase;

/**
 * Development label resolution for authenticated plot monitoring (linked post title only).
 */
final class PlotDatasetMapperDevelopmentTest extends TestCase
{
	/** @var array<int, array<string, mixed>> */
	private array $metaBackup = [];

	/** @var array<int, \WP_Post> */
	private array $postsBackup = [];

	/** @var array<int, string> */
	private array $titlesBackup = [];

	protected function setUp(): void
	{
		parent::setUp();
		$this->metaBackup = $GLOBALS['contextualwp_housebuilder_test_post_meta'] ?? [];
		$this->postsBackup = $GLOBALS['contextualwp_housebuilder_test_posts'] ?? [];
		$this->titlesBackup = $GLOBALS['contextualwp_housebuilder_test_post_titles'] ?? [];
	}

	protected function tearDown(): void
	{
		if ($this->metaBackup === []) {
			unset($GLOBALS['contextualwp_housebuilder_test_post_meta']);
		} else {
			$GLOBALS['contextualwp_housebuilder_test_post_meta'] = $this->metaBackup;
		}
		if ($this->postsBackup === []) {
			unset($GLOBALS['contextualwp_housebuilder_test_posts']);
		} else {
			$GLOBALS['contextualwp_housebuilder_test_posts'] = $this->postsBackup;
		}
		if ($this->titlesBackup === []) {
			unset($GLOBALS['contextualwp_housebuilder_test_post_titles']);
		} else {
			$GLOBALS['contextualwp_housebuilder_test_post_titles'] = $this->titlesBackup;
		}
		parent::tearDown();
	}

	public function testMapPostIncludesPublishedLinkedDevelopmentName(): void
	{
		$this->seedLinkedDevelopment(9001, 'publish', 'Brimsmore Townhouse Collection');

		$mapped = PlotDatasetMapper::mapPost($this->plotPost(501, 9001), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertSame('Brimsmore Townhouse Collection', $mapped['development']);
		$this->assertResponseShapeKeys($mapped);
	}

	public function testMapPostIncludesDraftLinkedDevelopmentNameForMonitoring(): void
	{
		$this->seedLinkedDevelopment(9002, 'draft', 'Brimsmore Townhouse Collection');

		$mapped = PlotDatasetMapper::mapPost($this->plotPost(502, 9002), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertSame('Brimsmore Townhouse Collection', $mapped['development']);
		$this->assertResponseShapeKeys($mapped);
	}

	public function testMapPostOmitsTrashedLinkedDevelopmentName(): void
	{
		$this->seedLinkedDevelopment(9003, 'trash', 'Brimsmore Townhouse Collection');

		$mapped = PlotDatasetMapper::mapPost($this->plotPost(503, 9003), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertNull($mapped['development']);
	}

	public function testMapPostStillRequiresPublishedLinkedHouseType(): void
	{
		$plotId = 504;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => [
				'development' => 9100,
				'house_type' => 9200,
			],
		];
		$GLOBALS['contextualwp_housebuilder_test_posts'] = [
			9100 => $this->linkedPost(9100, 'draft'),
			9200 => $this->linkedPost(9200, 'draft'),
		];
		$GLOBALS['contextualwp_housebuilder_test_post_titles'] = [
			9100 => 'Draft Development',
			9200 => 'Draft House Type',
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId, 9100), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertSame('Draft Development', $mapped['development']);
		$this->assertNull($mapped['house_type']);
	}

	private function seedLinkedDevelopment(int $developmentId, string $status, string $title): void
	{
		$GLOBALS['contextualwp_housebuilder_test_posts'] = [
			$developmentId => $this->linkedPost($developmentId, $status),
		];
		$GLOBALS['contextualwp_housebuilder_test_post_titles'] = [
			$developmentId => $title,
		];
	}

	private function plotPost(int $plotId, ?int $developmentId = null): \WP_Post
	{
		if ($developmentId !== null) {
			$existing = $GLOBALS['contextualwp_housebuilder_test_post_meta'][$plotId] ?? [];
			$GLOBALS['contextualwp_housebuilder_test_post_meta'][$plotId] = \array_merge(
				\is_array($existing) ? $existing : [],
				['development' => $developmentId]
			);
		}

		$post = new \WP_Post();
		$post->ID = $plotId;
		$post->post_type = 'plot';
		$post->post_status = 'publish';
		$post->post_modified_gmt = '2026-01-15 12:00:00';

		return $post;
	}

	private function linkedPost(int $id, string $status): \WP_Post
	{
		$post = new \WP_Post();
		$post->ID = $id;
		$post->post_type = 'development';
		$post->post_status = $status;

		return $post;
	}

	/**
	 * @param array<string, mixed> $mapped
	 */
	private function assertResponseShapeKeys(array $mapped): void
	{
		$this->assertSame(
			[
				'id',
				'wp_id',
				'title',
				'status',
				'price',
				'bedrooms',
				'development',
				'house_type',
				'url',
				'last_updated',
				'last_modified_by',
				'has_floor_plan',
				'floor_plan_required',
				'floor_plan_completeness_status',
				'has_intro_video',
				'has_intro_image',
				'intro_media_type',
				'intro_media_completeness_status',
			],
			\array_keys($mapped)
		);
	}
}
