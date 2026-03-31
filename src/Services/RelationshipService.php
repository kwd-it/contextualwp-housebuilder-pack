<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

/**
 * Adds manifest-level relationship hints from registered CPTs, taxonomies, and ACF field targets.
 *
 * @see https://github.com/kwd-it/contextualwp/blob/main/includes/endpoints/manifest.php (contextualwp_manifest_schema_relationships)
 */
final class RelationshipService
{
	private const DEV_CPT = 'developments';
	private const PLOT_CPT = 'plots';
	private const FUTURE_DEV_CPT = 'future_developments';

	public function register(): void
	{
		\add_filter('contextualwp_manifest_schema_relationships', [$this, 'filterManifestRelationships'], 10, 1);
	}

	/**
	 * @param array<int, array<string, string>> $relationships
	 * @return array<int, array<string, string>>
	 */
	public function filterManifestRelationships(array $relationships): array
	{
		$added = $this->collectRelationships();

		return \array_values(\array_merge($relationships, $added));
	}

	/**
	 * @return list<array{source_type: string, target_type: string, description: string, evidence?: string}>
	 */
	private function collectRelationships(): array
	{
		$out = [];

		$hasDev = SiteStructureHints::hasPublicPostType(self::DEV_CPT);
		$hasPlots = SiteStructureHints::hasPublicPostType(self::PLOT_CPT);
		$hasFuture = SiteStructureHints::hasPublicPostType(self::FUTURE_DEV_CPT);

		if ($hasDev && $hasPlots) {
			$evidence = [];
			if (SiteStructureHints::acfLinksPostTypes(self::DEV_CPT, self::PLOT_CPT)) {
				$evidence[] = 'ACF relationship/post_object on developments targets plots.';
			}
			if (SiteStructureHints::acfLinksPostTypes(self::PLOT_CPT, self::DEV_CPT)) {
				$evidence[] = 'ACF relationship/post_object on plots targets developments.';
			}

			$desc = __(
				'Active developments (sites) typically own or group individual plot records. Links are often implemented with ACF relationship/post_object fields or a shared taxonomy.',
				'contextualwp-housebuilder-pack'
			);
			$row = [
				'source_type' => self::DEV_CPT,
				'target_type' => self::PLOT_CPT,
				'description' => $desc,
			];
			if ($evidence !== []) {
				$row['evidence'] = \implode(' ', $evidence);
			}
			$out[] = $row;

			$out[] = [
				'source_type' => self::PLOT_CPT,
				'target_type' => self::DEV_CPT,
				'description' => __(
					'Plot records usually reference their parent development or site when both content types exist.',
					'contextualwp-housebuilder-pack'
				),
			];
		}

		if ($hasPlots) {
			foreach (\get_taxonomies(['public' => true], 'objects') as $tax) {
				$name = $tax->name;
				if (!SiteStructureHints::taxonomyAppliesToPostType($name, self::PLOT_CPT)) {
					continue;
				}
				if (!SiteStructureHints::isGenericDevelopmentTaxonomy($name)) {
					continue;
				}
				// Only assert taxonomy↔plots when taxonomy is not broadly shared across unrelated CPTs.
				$objects = \is_array($tax->object_type) ? $tax->object_type : [];
				if (\count($objects) > 1) {
					continue;
				}

				$out[] = [
					'source_type' => $name,
					'target_type' => self::PLOT_CPT,
					'description' => \sprintf(
						/* translators: 1: taxonomy slug */
						__(
							'Terms in the "%1$s" taxonomy classify plot content. In many housebuilder builds this aligns with a development or scheme label.',
							'contextualwp-housebuilder-pack'
						),
						$name
					),
				];
			}
		}

		if ($hasFuture && $hasDev) {
			$out[] = [
				'source_type' => self::FUTURE_DEV_CPT,
				'target_type' => self::DEV_CPT,
				'description' => __(
					'Future or pipeline development content may correspond to live development records once schemes launch; cross-links depend on editorial or field configuration.',
					'contextualwp-housebuilder-pack'
				),
			];
		}

		return $out;
	}
}
