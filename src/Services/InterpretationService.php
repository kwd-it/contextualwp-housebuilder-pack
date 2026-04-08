<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

/**
 * Sector interpretation for REST schema (post types, taxonomies) and manifest schema mirror.
 */
final class InterpretationService
{
	public function register(): void
	{
		\add_filter('contextualwp_schema', [$this, 'filterRestSchema'], 20, 1);
		\add_filter('contextualwp_schema_interpretation', [$this, 'filterSchemaInterpretation'], 10, 2);
		\add_filter('contextualwp_manifest_schema', [$this, 'filterManifestSchema'], 20, 1);
	}

	/**
	 * @param array<string, mixed> $schema
	 * @return array<string, mixed>
	 */
	public function filterRestSchema(array $schema): array
	{
		if (empty($schema['post_types']) || !\is_array($schema['post_types'])) {
			return $schema;
		}

		$schema['post_types'] = $this->enrichPostTypeList($schema['post_types']);

		return $schema;
	}

	/**
	 * @param array<string, mixed> $schema
	 * @return array<string, mixed>
	 */
	public function filterManifestSchema(array $schema): array
	{
		if (empty($schema['post_types']) || !\is_array($schema['post_types'])) {
			return $schema;
		}

		$schema['post_types'] = $this->enrichManifestPostTypes($schema['post_types']);

		return $schema;
	}

	/**
	 * @param array<int, array<string, mixed>> $postTypes
	 * @return array<int, array<string, mixed>>
	 */
	private function enrichPostTypeList(array $postTypes): array
	{
		$defs = $this->postTypeInterpretations();

		foreach ($postTypes as $i => $pt) {
			$slug = isset($pt['slug']) ? (string) $pt['slug'] : '';
			if ($slug === '' || !isset($defs[$slug])) {
				continue;
			}
			$postTypes[$i]['housebuilder_pack'] = $defs[$slug];
		}

		return $postTypes;
	}

	/**
	 * Manifest entries use `name` instead of `slug` for the post type key.
	 *
	 * @param array<int, array<string, mixed>> $postTypes
	 * @return array<int, array<string, mixed>>
	 */
	private function enrichManifestPostTypes(array $postTypes): array
	{
		$defs = $this->postTypeInterpretations();

		foreach ($postTypes as $i => $pt) {
			$slug = isset($pt['name']) ? (string) $pt['name'] : '';
			if ($slug === '' || !isset($defs[$slug])) {
				continue;
			}
			$postTypes[$i]['housebuilder_pack'] = $defs[$slug];
		}

		return $postTypes;
	}

	/**
	 * @param array<string, mixed> $interpretation
	 * @param array<string, mixed> $schema
	 * @return array<string, mixed>
	 */
	public function filterSchemaInterpretation(array $interpretation, array $schema): array
	{
		$pack = [
			'slug' => 'housebuilder',
			'post_types' => $this->postTypeInterpretations(),
			'taxonomies' => $this->taxonomyInterpretations(),
		];

		// Merge if another extension already contributed typed keys.
		if ($interpretation === []) {
			return ['housebuilder_pack' => $pack];
		}

		$interpretation['housebuilder_pack'] = $pack;

		return $interpretation;
	}

	/**
	 * @return array<string, array{entity_kind: string, summary: string, typical_use: string}>
	 */
	private function postTypeInterpretations(): array
	{
		$out = [];

		foreach (HousebuilderStructuralSignals::publicPostTypeObjects() as $pt) {
			$profile = HousebuilderStructuralSignals::postTypeInterpretationProfile($pt);
			if ($profile === null) {
				continue;
			}
			$kind = $profile['entity_kind'];
			$slug = $pt->name;
			if ($kind === 'housing_development_site') {
				$out[$slug] = [
					'entity_kind' => $kind,
					'summary' => __(
						'A live housing development or site: branding, narrative, and location context for a collection of plots.',
						'contextualwp-housebuilder-pack'
					),
					'typical_use' => __(
						'Landing pages for schemes; often parent or anchor content for plot listings.',
						'contextualwp-housebuilder-pack'
					),
				];
			} elseif ($kind === 'plot_or_unit') {
				$out[$slug] = [
					'entity_kind' => $kind,
					'summary' => __(
						'An individual building plot or property unit within a development, including availability and specification-oriented fields.',
						'contextualwp-housebuilder-pack'
					),
					'typical_use' => __(
						'Per-home detail pages; frequently filtered by development taxonomy or linked to a parent development record.',
						'contextualwp-housebuilder-pack'
					),
				];
			} elseif ($kind === 'pipeline_development_content') {
				$out[$slug] = [
					'entity_kind' => $kind,
					'summary' => __(
						'Editorial or land-and-planning content about forthcoming schemes before they become active sales sites.',
						'contextualwp-housebuilder-pack'
					),
					'typical_use' => __(
						'Pipeline storytelling, consultations, or early-stage scheme announcements.',
						'contextualwp-housebuilder-pack'
					),
				];
			} elseif ($kind === 'house_type_or_property_model') {
				$out[$slug] = [
					'entity_kind' => $kind,
					'summary' => __(
						'A reusable house type or property model (layout/spec catalogue), often linked from multiple plot records.',
						'contextualwp-housebuilder-pack'
					),
					'typical_use' => __(
						'Shared templates for bedroom counts, floor plans, and model-level media reused across developments.',
						'contextualwp-housebuilder-pack'
					),
				];
			}
		}

		return $out;
	}

	/**
	 * @return array<string, array{role: string, summary: string}>
	 */
	private function taxonomyInterpretations(): array
	{
		$out = [];

		if (SiteStructureHints::plotLikePublicPostTypeSlugs() === []) {
			return $out;
		}

		foreach (\get_taxonomies(['public' => true], 'objects') as $tax) {
			if (!$tax instanceof \WP_Taxonomy) {
				continue;
			}
			if (!SiteStructureHints::isPlotDevelopmentClassifierTaxonomy($tax)) {
				continue;
			}
			$name = $tax->name;
			$out[$name] = [
				'role' => 'plot_development_classifier',
				'summary' => \sprintf(
					/* translators: %s: taxonomy slug */
					\__(
						'The "%s" taxonomy classifies plot-like content by development, site, or scheme (registered only against plot-like post types).',
						'contextualwp-housebuilder-pack'
					),
					$name
				),
			];
		}

		return $out;
	}
}
