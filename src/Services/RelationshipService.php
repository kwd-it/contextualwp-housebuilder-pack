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

		// Endpoints are only CPTs that pass strict structural classifiers (no blocklist hits, no label-only plot inference).
		$devSlugs = SiteStructureHints::developmentSiteLikePublicPostTypeSlugs();
		$plotSlugs = SiteStructureHints::plotLikePublicPostTypeSlugs();
		$futureSlugs = SiteStructureHints::pipelineDevelopmentLikePublicPostTypeSlugs();

		foreach ($devSlugs as $dev) {
			foreach ($plotSlugs as $plot) {
				$evidence = [];
				if (SiteStructureHints::acfLinksPostTypes($dev, $plot)) {
					$evidence[] = \sprintf(
						/* translators: 1: source post type slug, 2: target post type slug */
						\__('ACF relationship/post_object on %1$s targets %2$s.', 'contextualwp-housebuilder-pack'),
						$dev,
						$plot
					);
				}
				if (SiteStructureHints::acfLinksPostTypes($plot, $dev)) {
					$evidence[] = \sprintf(
						/* translators: 1: source post type slug, 2: target post type slug */
						\__('ACF relationship/post_object on %1$s targets %2$s.', 'contextualwp-housebuilder-pack'),
						$plot,
						$dev
					);
				}

				$desc = __(
					'Active developments (sites) typically own or group individual plot records. Links are often implemented with ACF relationship/post_object fields or a shared taxonomy.',
					'contextualwp-housebuilder-pack'
				);
				$row = [
					'source_type' => $dev,
					'target_type' => $plot,
					'description' => $desc,
				];
				if ($evidence !== []) {
					$row['evidence'] = \implode(' ', $evidence);
				}
				$out[] = $row;

				$out[] = [
					'source_type' => $plot,
					'target_type' => $dev,
					'description' => __(
						'Plot records usually reference their parent development or site when both content types exist.',
						'contextualwp-housebuilder-pack'
					),
				];
			}
		}

		if ($plotSlugs !== []) {
			foreach (\get_taxonomies(['public' => true], 'objects') as $tax) {
				if (!$tax instanceof \WP_Taxonomy) {
					continue;
				}
				if (!SiteStructureHints::isPlotDevelopmentClassifierTaxonomy($tax)) {
					continue;
				}
				$name = $tax->name;
				foreach ($plotSlugs as $plot) {
					if (!SiteStructureHints::taxonomyAppliesToPostType($name, $plot)) {
						continue;
					}
					$out[] = [
						'source_type' => $name,
						'target_type' => $plot,
						'description' => \sprintf(
							/* translators: 1: taxonomy slug, 2: plot-like post type slug */
							\__(
								'Terms in the "%1$s" taxonomy classify "%2$s" content. In many housebuilder builds this aligns with a development or scheme label.',
								'contextualwp-housebuilder-pack'
							),
							$name,
							$plot
						),
					];
				}
			}
		}

		foreach ($futureSlugs as $future) {
			foreach ($devSlugs as $dev) {
				$out[] = [
					'source_type' => $future,
					'target_type' => $dev,
					'description' => __(
						'Future or pipeline development content may correspond to live development records once schemes launch; cross-links depend on editorial or field configuration.',
						'contextualwp-housebuilder-pack'
					),
				];
			}
		}

		return $out;
	}
}
