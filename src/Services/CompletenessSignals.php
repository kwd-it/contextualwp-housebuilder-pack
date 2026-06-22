<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

/**
 * Builds conservative asset completeness signals for plot and development monitoring exports.
 *
 * Signals report presence / missing / unknown only; downstream tools decide whether to raise issues.
 */
final class CompletenessSignals
{
	/**
	 * @param array<string, list<string>> $metaKeys
	 * @return array<string, mixed>
	 */
	public static function plotSignals(\WP_Post $plot, array $metaKeys): array
	{
		$floorPlanKeys = $metaKeys['floor_plan'] ?? [];
		$hasFloorPlan = AssetPresenceHelper::hasPresentMeta((int) $plot->ID, $floorPlanKeys);

		$requiredRaw = \apply_filters(
			'contextualwp_housebuilder_plot_floor_plan_required',
			null,
			$plot,
			$metaKeys
		);
		$floorPlanRequired = self::coerceNullableBool($requiredRaw);

		$signals = [
			'has_floor_plan' => $hasFloorPlan,
			'floor_plan_required' => $floorPlanRequired,
			'floor_plan_completeness_status' => self::assetCompletenessStatus($hasFloorPlan, $floorPlanRequired),
		];

		$filtered = \apply_filters('contextualwp_housebuilder_plot_completeness_signals', $signals, $plot, $metaKeys);

		return \is_array($filtered) ? $filtered : $signals;
	}

	/**
	 * @param array<string, list<string>> $developmentMetaKeys
	 * @return array<string, mixed>
	 */
	public static function developmentSignals(?\WP_Post $development, array $developmentMetaKeys): array
	{
		if (!$development instanceof \WP_Post) {
			return self::unknownDevelopmentSignals();
		}

		$videoKeys = $developmentMetaKeys['intro_video'] ?? [];
		$imageKeys = $developmentMetaKeys['intro_image'] ?? [];

		$hasIntroVideo = AssetPresenceHelper::hasPresentMeta((int) $development->ID, $videoKeys, 'video');
		$hasIntroImage = AssetPresenceHelper::hasPresentMeta((int) $development->ID, $imageKeys, 'image');

		$requiredRaw = \apply_filters(
			'contextualwp_housebuilder_development_intro_media_required',
			null,
			$development,
			(int) $development->ID
		);
		$introMediaRequired = self::coerceNullableBool($requiredRaw);

		$signals = [
			'has_intro_video' => $hasIntroVideo,
			'has_intro_image' => $hasIntroImage,
			'intro_media_type' => self::introMediaType($hasIntroVideo, $hasIntroImage),
			'intro_media_completeness_status' => self::introMediaCompletenessStatus(
				$hasIntroVideo,
				$hasIntroImage,
				$introMediaRequired
			),
		];

		$filtered = \apply_filters(
			'contextualwp_housebuilder_development_completeness_signals',
			$signals,
			$development,
			$developmentMetaKeys
		);

		return \is_array($filtered) ? $filtered : $signals;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function unknownDevelopmentSignals(): array
	{
		return [
			'has_intro_video' => null,
			'has_intro_image' => null,
			'intro_media_type' => null,
			'intro_media_completeness_status' => 'unknown',
		];
	}

	/**
	 * @return array<string, list<string>>
	 */
	public static function defaultDevelopmentMetaKeyMap(): array
	{
		return [
			'intro_video' => [
				'intro_video',
				'video',
				'hero_video',
				'development_video',
				'site_video',
				'vimeo_url',
				'video_url',
				'intro_vimeo',
			],
			'intro_image' => [
				'intro_image',
				'hero_image',
				'development_image',
				'site_image',
				'intro_hero',
				'hero',
			],
		];
	}

	private static function coerceNullableBool(mixed $raw): ?bool
	{
		if ($raw === null) {
			return null;
		}
		if (\is_bool($raw)) {
			return $raw;
		}

		return null;
	}

	private static function assetCompletenessStatus(?bool $hasAsset, ?bool $required): string
	{
		if ($hasAsset === true) {
			return 'present';
		}
		if ($hasAsset === false && $required === true) {
			return 'missing';
		}

		return 'unknown';
	}

	private static function introMediaCompletenessStatus(
		?bool $hasIntroVideo,
		?bool $hasIntroImage,
		?bool $required
	): string {
		if ($hasIntroVideo === true || $hasIntroImage === true) {
			return 'present';
		}
		if ($required === true && $hasIntroVideo === false && $hasIntroImage === false) {
			return 'missing';
		}

		return 'unknown';
	}

	private static function introMediaType(?bool $hasIntroVideo, ?bool $hasIntroImage): ?string
	{
		if ($hasIntroVideo === null && $hasIntroImage === null) {
			return null;
		}
		if ($hasIntroVideo === true) {
			return 'video';
		}
		if ($hasIntroImage === true) {
			return 'image';
		}

		return 'none';
	}
}
