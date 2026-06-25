<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Tests;

use ContextualWP\HousebuilderPack\Services\PlotDatasetMapper;
use PHPUnit\Framework\TestCase;

/**
 * Asset completeness signals on the authenticated plots REST mapping.
 *
 * Completeness status is present / missing / unknown only. `unknown` is non-actionable: it covers
 * both undetermined requirements and assets that are explicitly not required for monitoring.
 */
final class PlotDatasetMapperCompletenessTest extends TestCase
{
	/** @var array<int, array<string, mixed>> */
	private array $metaBackup = [];

	/** @var array<int, \WP_Post> */
	private array $postsBackup = [];

	/** @var array<int, string> */
	private array $titlesBackup = [];

	/** @var array<string, mixed> */
	private array $filtersBackup = [];

	protected function setUp(): void
	{
		parent::setUp();
		$this->metaBackup = $GLOBALS['contextualwp_housebuilder_test_post_meta'] ?? [];
		$this->postsBackup = $GLOBALS['contextualwp_housebuilder_test_posts'] ?? [];
		$this->titlesBackup = $GLOBALS['contextualwp_housebuilder_test_post_titles'] ?? [];
		$this->filtersBackup = $GLOBALS['contextualwp_housebuilder_test_filters'] ?? [];
	}

	protected function tearDown(): void
	{
		$this->restoreGlobal('contextualwp_housebuilder_test_post_meta', $this->metaBackup);
		$this->restoreGlobal('contextualwp_housebuilder_test_posts', $this->postsBackup);
		$this->restoreGlobal('contextualwp_housebuilder_test_post_titles', $this->titlesBackup);
		$this->restoreGlobal('contextualwp_housebuilder_test_filters', $this->filtersBackup);
		parent::tearDown();
	}

	public function testPlotWithFloorPlanPresent(): void
	{
		$plotId = 601;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['floor_plan' => 4401],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertTrue($mapped['has_floor_plan']);
		$this->assertNull($mapped['floor_plan_required']);
		$this->assertSame('present', $mapped['floor_plan_completeness_status']);
	}

	public function testPlotWithoutFloorPlanWhenRequirementUndeterminedIsNonActionable(): void
	{
		$plotId = 602;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['floor_plan' => ''],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_floor_plan']);
		$this->assertNull($mapped['floor_plan_required']);
		$this->assertSame('unknown', $mapped['floor_plan_completeness_status']);
	}

	public function testAvailablePlotWithoutFloorPlanMarksMissing(): void
	{
		$plotId = 612;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => [
				'floor_plan' => '',
				'plot_status' => 'available',
			],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_floor_plan']);
		$this->assertTrue($mapped['floor_plan_required']);
		$this->assertSame('missing', $mapped['floor_plan_completeness_status']);
	}

	public function testPlotWithFloorPlanInAcfBlockContentIsPresent(): void
	{
		$plotId = 620;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['plot_status' => 'available'],
		];
		$content = $this->plotFloorPlansBlock('{"floors":"2","floors_0_floorplan":"10376","floors_1_floorplan":"10377"}');

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId, $content), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertTrue($mapped['has_floor_plan']);
		$this->assertSame('present', $mapped['floor_plan_completeness_status']);
	}

	public function testPlotWithEmptyAcfBlockFloorPlanValuesMarksMissingWhenRequired(): void
	{
		$plotId = 621;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['plot_status' => 'available'],
		];
		$content = $this->plotFloorPlansBlock('{"floors":"2","floors_0_floorplan":"","floors_1_floorplan":""}');

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId, $content), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_floor_plan']);
		$this->assertTrue($mapped['floor_plan_required']);
		$this->assertSame('missing', $mapped['floor_plan_completeness_status']);
	}

	public function testPlotFloorPlanPostMetaDetectionStillWorksWithoutBlockContent(): void
	{
		$plotId = 622;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['floor_plan' => 4444, 'plot_status' => 'available'],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertTrue($mapped['has_floor_plan']);
		$this->assertSame('present', $mapped['floor_plan_completeness_status']);
	}

	public function testSoldPlotWithoutFloorPlanIsNonRequiredAndNonActionable(): void
	{
		$plotId = 613;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => [
				'floor_plan' => '',
				'plot_status' => 'sold',
			],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_floor_plan']);
		$this->assertFalse($mapped['floor_plan_required']);
		// Sold plots do not require a floor plan; status stays unknown because the enum has no "not required" value.
		$this->assertSame('unknown', $mapped['floor_plan_completeness_status']);
	}

	public function testPlotFloorPlanExplicitlyNotRequiredIsNonActionableWhenMissing(): void
	{
		$plotId = 603;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['floor_plan' => ''],
		];
		$GLOBALS['contextualwp_housebuilder_test_filters'] = [
			'contextualwp_housebuilder_plot_floor_plan_required' => static fn (): bool => false,
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_floor_plan']);
		$this->assertFalse($mapped['floor_plan_required']);
		$this->assertSame('unknown', $mapped['floor_plan_completeness_status']);
	}

	public function testPlotFloorPlanRequiredViaFilterMarksMissing(): void
	{
		$plotId = 604;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['floor_plan' => ''],
		];
		$GLOBALS['contextualwp_housebuilder_test_filters'] = [
			'contextualwp_housebuilder_plot_floor_plan_required' => static fn (): bool => true,
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_floor_plan']);
		$this->assertTrue($mapped['floor_plan_required']);
		$this->assertSame('missing', $mapped['floor_plan_completeness_status']);
	}

	public function testDevelopmentWithIntroVideoPresent(): void
	{
		$plotId = 605;
		$developmentId = 9605;
		$this->seedLinkedDevelopment($developmentId, 'publish', 'Riverside');
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['development' => $developmentId],
			$developmentId => ['intro_video' => 'https://vimeo.com/123456789'],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertTrue($mapped['has_intro_video']);
		$this->assertFalse($mapped['has_intro_image']);
		$this->assertSame('video', $mapped['intro_media_type']);
		$this->assertSame('present', $mapped['intro_media_completeness_status']);
	}

	/**
	 * Intro videos exist in many stored shapes; none should be reported as "missing".
	 *
	 * @dataProvider provideIntroVideoShapes
	 *
	 * @param mixed $storedValue
	 */
	public function testDevelopmentWithIntroVideoShapeIsPresent(string $metaKey, $storedValue): void
	{
		$plotId = 650;
		$developmentId = 9650;
		$this->seedLinkedDevelopment($developmentId, 'publish', 'Wired');
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['development' => $developmentId],
			$developmentId => [$metaKey => $storedValue],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertTrue($mapped['has_intro_video'], 'Video shape should be detected as present');
		$this->assertSame('video', $mapped['intro_media_type']);
		$this->assertSame('present', $mapped['intro_media_completeness_status']);
	}

	/**
	 * @return array<string, array{0: string, 1: mixed}>
	 */
	public static function provideIntroVideoShapes(): array
	{
		return [
			'plain vimeo url' => ['intro_video', 'https://vimeo.com/123456789'],
			'bare vimeo id string' => ['intro_video', '824804225'],
			'self-hosted file url' => ['intro_video', 'https://cdn.example.test/intro.mp4'],
			'attachment id int' => ['intro_video', 4455],
			'attachment id numeric string' => ['intro_video', '4455'],
			'oembed array' => ['video_oembed', ['url' => 'https://vimeo.com/55', 'value' => '<iframe></iframe>']],
			'embed html string' => ['video_embed', '<iframe src="https://player.example.test/v/1"></iframe>'],
			'acf media array' => ['intro_video', ['ID' => 4455, 'mime_type' => 'video/mp4']],
			'nested group with src' => ['intro_video', ['src' => 'https://cdn.example.test/intro.webm']],
			'youtube url' => ['youtube_url', 'https://www.youtube.com/watch?v=abc'],
		];
	}

	public function testDevelopmentWithIntroImagePresent(): void
	{
		$plotId = 606;
		$developmentId = 9606;
		$this->seedLinkedDevelopment($developmentId, 'publish', 'Meadow View');
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['development' => $developmentId],
			$developmentId => ['intro_image' => ['ID' => 8801, 'url' => 'https://example.test/hero.jpg']],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_intro_video']);
		$this->assertTrue($mapped['has_intro_image']);
		$this->assertSame('image', $mapped['intro_media_type']);
		$this->assertSame('present', $mapped['intro_media_completeness_status']);
	}

	/**
	 * @dataProvider provideIntroImageShapes
	 *
	 * @param mixed $storedValue
	 */
	public function testDevelopmentWithIntroImageShapeIsPresent(string $metaKey, $storedValue): void
	{
		$plotId = 660;
		$developmentId = 9660;
		$this->seedLinkedDevelopment($developmentId, 'publish', 'Wyatt');
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['development' => $developmentId],
			$developmentId => [$metaKey => $storedValue],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_intro_video']);
		$this->assertTrue($mapped['has_intro_image'], 'Image shape should be detected as present');
		$this->assertSame('image', $mapped['intro_media_type']);
		$this->assertSame('present', $mapped['intro_media_completeness_status']);
	}

	/**
	 * @return array<string, array{0: string, 1: mixed}>
	 */
	public static function provideIntroImageShapes(): array
	{
		return [
			'acf image array' => ['intro_image', ['ID' => 8801, 'url' => 'https://example.test/hero.jpg']],
			'plain image url' => ['intro_image', 'https://example.test/hero.jpg'],
			'attachment id int' => ['hero_image', 8802],
			'attachment id numeric string' => ['intro_image', '8803'],
			'nested group with src' => ['intro_image', ['src' => 'https://example.test/banner.webp']],
		];
	}

	public function testPublishedDevelopmentWithoutIntroMediaMarksMissingByDefault(): void
	{
		$plotId = 607;
		$developmentId = 9607;
		$this->seedLinkedDevelopment($developmentId, 'publish', 'Oak Park');
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['development' => $developmentId],
			$developmentId => ['intro_video' => '', 'intro_image' => ''],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_intro_video']);
		$this->assertFalse($mapped['has_intro_image']);
		$this->assertSame('none', $mapped['intro_media_type']);
		$this->assertSame('missing', $mapped['intro_media_completeness_status']);
	}

	public function testDraftDevelopmentWithoutIntroMediaIsNonActionableByDefault(): void
	{
		$plotId = 615;
		$developmentId = 9615;
		$this->seedLinkedDevelopment($developmentId, 'draft', 'Pipeline Site');
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['development' => $developmentId],
			$developmentId => ['intro_video' => '', 'intro_image' => ''],
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_intro_video']);
		$this->assertFalse($mapped['has_intro_image']);
		$this->assertSame('none', $mapped['intro_media_type']);
		// Non-published developments are not required to carry intro media by default.
		$this->assertSame('unknown', $mapped['intro_media_completeness_status']);
	}

	public function testDevelopmentIntroMediaExplicitlyNotRequiredIsNonActionableWhenMissing(): void
	{
		$plotId = 614;
		$developmentId = 9614;
		$this->seedLinkedDevelopment($developmentId, 'publish', 'Westgate');
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['development' => $developmentId],
			$developmentId => ['intro_video' => '', 'intro_image' => ''],
		];
		$GLOBALS['contextualwp_housebuilder_test_filters'] = [
			'contextualwp_housebuilder_development_intro_media_required' => static fn (): bool => false,
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertFalse($mapped['has_intro_video']);
		$this->assertFalse($mapped['has_intro_image']);
		$this->assertSame('unknown', $mapped['intro_media_completeness_status']);
	}

	public function testDevelopmentIntroMediaRequiredViaFilterMarksMissing(): void
	{
		$plotId = 608;
		$developmentId = 9608;
		$this->seedLinkedDevelopment($developmentId, 'publish', 'Harbour');
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['development' => $developmentId],
			$developmentId => ['intro_video' => '', 'intro_image' => ''],
		];
		$GLOBALS['contextualwp_housebuilder_test_filters'] = [
			'contextualwp_housebuilder_development_intro_media_required' => static fn (): bool => true,
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertSame('missing', $mapped['intro_media_completeness_status']);
	}

	public function testPlotCompletenessSignalsFilterCanOverride(): void
	{
		$plotId = 609;
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['floor_plan' => 4409],
		];
		$GLOBALS['contextualwp_housebuilder_test_filters'] = [
			'contextualwp_housebuilder_plot_completeness_signals' => static function (array $signals): array {
				$signals['floor_plan_completeness_status'] = 'unknown';

				return $signals;
			},
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertTrue($mapped['has_floor_plan']);
		$this->assertSame('unknown', $mapped['floor_plan_completeness_status']);
	}

	public function testDevelopmentCompletenessSignalsFilterCanOverride(): void
	{
		$plotId = 610;
		$developmentId = 9610;
		$this->seedLinkedDevelopment($developmentId, 'publish', 'Lakeside');
		$GLOBALS['contextualwp_housebuilder_test_post_meta'] = [
			$plotId => ['development' => $developmentId],
			$developmentId => ['intro_video' => 'https://vimeo.com/999'],
		];
		$GLOBALS['contextualwp_housebuilder_test_filters'] = [
			'contextualwp_housebuilder_development_completeness_signals' => static function (array $signals): array {
				$signals['intro_media_completeness_status'] = 'unknown';

				return $signals;
			},
		];

		$mapped = PlotDatasetMapper::mapPost($this->plotPost($plotId), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertTrue($mapped['has_intro_video']);
		$this->assertSame('unknown', $mapped['intro_media_completeness_status']);
	}

	public function testPlotWithoutLinkedDevelopmentHasUnknownIntroSignals(): void
	{
		$mapped = PlotDatasetMapper::mapPost($this->plotPost(611), PlotDatasetMapper::defaultMetaKeyMap());

		$this->assertNull($mapped['has_intro_video']);
		$this->assertNull($mapped['has_intro_image']);
		$this->assertNull($mapped['intro_media_type']);
		$this->assertSame('unknown', $mapped['intro_media_completeness_status']);
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

	private function plotPost(int $plotId, string $postContent = ''): \WP_Post
	{
		$post = new \WP_Post();
		$post->ID = $plotId;
		$post->post_type = 'plot';
		$post->post_status = 'publish';
		$post->post_modified_gmt = '2026-01-15 12:00:00';
		$post->post_content = $postContent;

		return $post;
	}

	private function plotFloorPlansBlock(string $dataJson): string
	{
		return '<!-- wp:acf/plot-floorplans {"name":"acf/plot-floorplans","data":' . $dataJson . ',"mode":"edit"} /-->';
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
	 * @param array<string, mixed> $backup
	 */
	private function restoreGlobal(string $key, array $backup): void
	{
		if ($backup === []) {
			unset($GLOBALS[$key]);
		} else {
			$GLOBALS[$key] = $backup;
		}
	}
}
