<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

/**
 * Read-only helpers for inferring housebuilder-relevant structure from WordPress registration data.
 */
final class SiteStructureHints
{
	public static function hasPublicPostType(string $slug): bool
	{
		$object = \get_post_type_object($slug);

		return $object !== null && !empty($object->public);
	}

	/**
	 * @return list<string>
	 */
	public static function publicPostTypeSlugs(): array
	{
		return HousebuilderStructuralSignals::publicPostTypeSlugs();
	}

	/**
	 * @return list<string>
	 */
	public static function plotLikePublicPostTypeSlugs(): array
	{
		$out = [];
		foreach (HousebuilderStructuralSignals::publicPostTypeObjects() as $pt) {
			if (HousebuilderStructuralSignals::postTypeIsPlotUnitLike($pt)) {
				$out[] = $pt->name;
			}
		}
		\sort($out, \SORT_STRING);

		return $out;
	}

	/**
	 * @return list<string>
	 */
	public static function developmentSiteLikePublicPostTypeSlugs(): array
	{
		$out = [];
		foreach (HousebuilderStructuralSignals::publicPostTypeObjects() as $pt) {
			if (HousebuilderStructuralSignals::postTypeIsDevelopmentSiteLike($pt)) {
				$out[] = $pt->name;
			}
		}
		\sort($out, \SORT_STRING);

		return $out;
	}

	/**
	 * @return list<string>
	 */
	public static function pipelineDevelopmentLikePublicPostTypeSlugs(): array
	{
		$out = [];
		foreach (HousebuilderStructuralSignals::publicPostTypeObjects() as $pt) {
			if (HousebuilderStructuralSignals::postTypeIsPipelineLike($pt)) {
				$out[] = $pt->name;
			}
		}
		\sort($out, \SORT_STRING);

		return $out;
	}

	public static function taxonomyAppliesToPostType(string $taxonomy, string $postType): bool
	{
		if (!\taxonomy_exists($taxonomy)) {
			return false;
		}

		return \is_object_in_taxonomy($postType, $taxonomy);
	}

	/**
	 * Whether taxonomy registration + slug/label signals look like a development/scheme classifier.
	 */
	public static function isGenericDevelopmentTaxonomy(string $taxonomySlug): bool
	{
		$tax = \get_taxonomy($taxonomySlug);

		return $tax instanceof \WP_Taxonomy
			&& HousebuilderStructuralSignals::taxonomySuggestsDevelopmentSchemeClassifier($tax);
	}

	/**
	 * Plot-classifier path: taxonomy applies only to recognised plot-like CPTs and passes naming signals.
	 */
	public static function isPlotDevelopmentClassifierTaxonomy(\WP_Taxonomy $tax): bool
	{
		if (!HousebuilderStructuralSignals::taxonomySuggestsDevelopmentSchemeClassifier($tax)) {
			return false;
		}

		return HousebuilderStructuralSignals::taxonomyAppliesOnlyToPlotLike(
			$tax,
			self::plotLikePublicPostTypeSlugs()
		);
	}

	/**
	 * Walks ACF field definitions for a post type to see if any relationship/post_object targets $targetPostType.
	 */
	public static function acfLinksPostTypes(string $fromPostType, string $targetPostType): bool
	{
		if (!\function_exists('acf_get_field_groups') || !\function_exists('acf_get_fields')) {
			return false;
		}

		$groups = \acf_get_field_groups(['post_type' => $fromPostType]);
		foreach ($groups as $group) {
			$fields = \acf_get_fields($group);
			if (!\is_array($fields)) {
				continue;
			}
			foreach (self::walkAcfFields($fields) as $field) {
				if (self::acfFieldReferencesPostType($field, $targetPostType)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param array<int, array<string, mixed>> $fields
	 * @return \Generator<int, array<string, mixed>>
	 */
	private static function walkAcfFields(array $fields): \Generator
	{
		foreach ($fields as $field) {
			yield $field;
			$sub = $field['sub_fields'] ?? null;
			if (\is_array($sub) && $sub !== []) {
				yield from self::walkAcfFields($sub);
			}
		}
	}

	/**
	 * @param array<string, mixed> $field
	 */
	private static function acfFieldReferencesPostType(array $field, string $targetPostType): bool
	{
		$type = isset($field['type']) ? (string) $field['type'] : '';
		if (!\in_array($type, ['relationship', 'post_object'], true)) {
			return false;
		}

		$pts = $field['post_type'] ?? [];
		if (!\is_array($pts)) {
			$pts = $pts !== '' && $pts !== null ? [(string) $pts] : [];
		}

		return \in_array($targetPostType, $pts, true);
	}
}
