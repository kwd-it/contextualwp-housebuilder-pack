<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

/**
 * Builds conservative asset completeness signals for plot and development monitoring exports.
 *
 * Signals report presence / missing / unknown only; downstream tools decide whether to raise issues.
 * The completeness status enum is intentionally limited: `unknown` means non-actionable, including
 * when a requirement is undetermined (null) or explicitly not required (false).
 */
final class CompletenessSignals
{
	/**
	 * Marketing-ready plot statuses for which a floor plan is required by default when detectable.
	 *
	 * @var list<string>
	 */
	private const PLOT_STATUSES_EXPECTING_FLOOR_PLAN = [
		'available',
		'released',
		'for sale',
		'for-sale',
		'on market',
		'on-market',
		'coming soon',
	];

	/**
	 * Plot statuses treated as not requiring a floor plan for monitoring (non-actionable when absent).
	 *
	 * @var list<string>
	 */
	private const PLOT_STATUSES_NOT_EXPECTING_FLOOR_PLAN = [
		'sold',
		'reserved',
		'under offer',
		'under-offer',
		'exchanged',
		'completed',
		'unavailable',
		'not available',
	];

	/**
	 * @param array<string, list<string>> $metaKeys
	 * @return array<string, mixed>
	 */
	public static function plotSignals(\WP_Post $plot, array $metaKeys): array
	{
		$floorPlanKeys = $metaKeys['floor_plan'] ?? [];
		$hasFloorPlan = AssetPresenceHelper::hasPresentMeta((int) $plot->ID, $floorPlanKeys);

		$requiredDefault = self::defaultPlotFloorPlanRequired($plot, $metaKeys, $hasFloorPlan);
		$requiredRaw = \apply_filters(
			'contextualwp_housebuilder_plot_floor_plan_required',
			$requiredDefault,
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

		$requiredDefault = self::defaultDevelopmentIntroMediaRequired(
			$development,
			$developmentMetaKeys,
			$hasIntroVideo,
			$hasIntroImage
		);
		$requiredRaw = \apply_filters(
			'contextualwp_housebuilder_development_intro_media_required',
			$requiredDefault,
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

	/**
	 * Generic default: marketing-ready plots require a floor plan when floor-plan meta keys are configured.
	 *
	 * Sold, reserved, and similar non-marketing statuses return false (not required). Unrecognised or
	 * missing status returns null (requirement undetermined).
	 *
	 * @param array<string, list<string>> $metaKeys
	 */
	public static function defaultPlotFloorPlanRequired(\WP_Post $plot, array $metaKeys, ?bool $hasFloorPlan): ?bool
	{
		if ($hasFloorPlan === null) {
			return null;
		}

		$status = PlotDatasetMapper::resolvedPlotStatus((int) $plot->ID, $metaKeys['status'] ?? []);

		return self::plotStatusExpectsFloorPlan($status);
	}

	/**
	 * Generic monitoring default: published linked developments are expected to carry intro media when
	 * intro video/image meta key candidates are configured and presence was checked on the development.
	 *
	 * Non-published developments (draft, private, pending, etc.) return null so pipeline content stays
	 * non-actionable; use contextualwp_housebuilder_development_intro_media_required for site rules.
	 *
	 * @param array<string, list<string>> $developmentMetaKeys
	 */
	public static function defaultDevelopmentIntroMediaRequired(
		\WP_Post $development,
		array $developmentMetaKeys,
		?bool $hasIntroVideo,
		?bool $hasIntroImage
	): ?bool {
		if ($hasIntroVideo === null && $hasIntroImage === null) {
			return null;
		}
		if ($development->post_status !== 'publish') {
			return null;
		}
		if (($developmentMetaKeys['intro_video'] ?? []) === [] && ($developmentMetaKeys['intro_image'] ?? []) === []) {
			return null;
		}

		return true;
	}

	/**
	 * Whether a normalised plot status implies a floor plan is required for monitoring.
	 *
	 * @return bool|null true = required, false = explicitly not required, null = undetermined
	 */
	public static function plotStatusExpectsFloorPlan(?string $status): ?bool
	{
		if ($status === null || $status === '') {
			return null;
		}

		$normalized = \strtolower(\trim($status));
		foreach (self::PLOT_STATUSES_NOT_EXPECTING_FLOOR_PLAN as $token) {
			if (self::statusMatchesToken($normalized, $token)) {
				return false;
			}
		}
		foreach (self::PLOT_STATUSES_EXPECTING_FLOOR_PLAN as $token) {
			if (self::statusMatchesToken($normalized, $token)) {
				return true;
			}
		}

		return null;
	}

	private static function statusMatchesToken(string $normalizedStatus, string $token): bool
	{
		$token = \strtolower(\trim($token));
		if ($normalizedStatus === $token) {
			return true;
		}

		$compactStatus = \preg_replace('/[\s\-_]+/', '', $normalizedStatus) ?? $normalizedStatus;
		$compactToken = \preg_replace('/[\s\-_]+/', '', $token) ?? $token;

		return $compactStatus === $compactToken;
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

		// Non-required or undetermined requirement: completeness is non-actionable for monitoring.
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

		// Non-required or undetermined requirement: completeness is non-actionable for monitoring.
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
