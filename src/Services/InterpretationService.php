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

		if (SiteStructureHints::hasPublicPostType('developments')) {
			$out['developments'] = [
				'entity_kind' => 'housing_development_site',
				'summary' => __(
					'A live housing development or site: branding, narrative, and location context for a collection of plots.',
					'contextualwp-housebuilder-pack'
				),
				'typical_use' => __(
					'Landing pages for schemes; often parent or anchor content for plot listings.',
					'contextualwp-housebuilder-pack'
				),
			];
		}

		if (SiteStructureHints::hasPublicPostType('plots')) {
			$out['plots'] = [
				'entity_kind' => 'plot_or_unit',
				'summary' => __(
					'An individual building plot or property unit within a development, including availability and specification-oriented fields.',
					'contextualwp-housebuilder-pack'
				),
				'typical_use' => __(
					'Per-home detail pages; frequently filtered by development taxonomy or linked to a parent development record.',
					'contextualwp-housebuilder-pack'
				),
			];
		}

		if (SiteStructureHints::hasPublicPostType('future_developments')) {
			$out['future_developments'] = [
				'entity_kind' => 'pipeline_development_content',
				'summary' => __(
					'Editorial or land-and-planning content about forthcoming schemes before they become active sales sites.',
					'contextualwp-housebuilder-pack'
				),
				'typical_use' => __(
					'Pipeline storytelling, consultations, or early-stage scheme announcements.',
					'contextualwp-housebuilder-pack'
				),
			];
		}

		return $out;
	}

	/**
	 * @return array<string, array{role: string, summary: string}>
	 */
	private function taxonomyInterpretations(): array
	{
		$out = [];

		if (!SiteStructureHints::hasPublicPostType('plots')) {
			return $out;
		}

		foreach (\get_taxonomies(['public' => true], 'objects') as $tax) {
			$name = $tax->name;
			if (!SiteStructureHints::taxonomyAppliesToPostType($name, 'plots')) {
				continue;
			}
			if (!SiteStructureHints::isGenericDevelopmentTaxonomy($name)) {
				continue;
			}
			$objects = \is_array($tax->object_type) ? $tax->object_type : [];
			if (\count($objects) > 1) {
				continue;
			}

			$out[$name] = [
				'role' => 'plot_development_classifier',
				'summary' => \sprintf(
					/* translators: %s: taxonomy slug */
					\__(
						'The "%s" taxonomy classifies plot posts by development or scheme (plots-only registration).',
						'contextualwp-housebuilder-pack'
					),
					$name
				),
			];
		}

		return $out;
	}
}
