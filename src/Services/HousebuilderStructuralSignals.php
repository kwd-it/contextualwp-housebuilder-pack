<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

/**
 * Normalisation and conservative token/synonym matching for housebuilder CPT/taxonomy detection.
 *
 * Prefers missed detections over false positives; no content inspection.
 */
final class HousebuilderStructuralSignals
{
	/**
	 * CPT slug tokens that indicate clearly non–housebuilder content (careers, editorial, etc.).
	 * If any token matches, the post type is never classified for pack entities or relationships.
	 *
	 * @var list<string>
	 */
	private const POST_TYPE_SLUG_EXCLUSION_TOKENS = [
		'career', 'careers', 'job', 'jobs', 'vacancy', 'vacancies', 'role', 'roles',
		'testimonial', 'testimonials', 'consultation', 'consultations', 'department', 'departments',
		'blog', 'blogs', 'news', 'posts',
	];

	/** @var list<string> */
	private const TAXONOMY_SLUG_BLOCKLIST = [
		'category', 'post_tag', 'post_format', 'nav_menu', 'link_category', 'wp_theme', 'wp_template_part_area',
		'wp_pattern_category', 'elementor_library_type', 'product_type', 'product_cat', 'product_tag',
	];

	/**
	 * @return list<\WP_Post_Type>
	 */
	public static function publicPostTypeObjects(): array
	{
		$objects = \get_post_types(['public' => true], 'objects');

		return \array_values(\array_filter(
			$objects,
			static fn ($pt): bool => $pt instanceof \WP_Post_Type && !empty($pt->public)
		));
	}

	/**
	 * @return list<string>
	 */
	public static function publicPostTypeSlugs(): array
	{
		return \array_map(
			static fn (\WP_Post_Type $pt): string => $pt->name,
			self::publicPostTypeObjects()
		);
	}

	/**
	 * Lowercase; hyphens → underscores for slug-style comparison.
	 */
	public static function normalizeSlug(string $slug): string
	{
		return \str_replace('-', '_', \strtolower(\trim($slug)));
	}

	public static function compactAlphanumeric(string $value): string
	{
		return \strtolower(\preg_replace('/[^a-z0-9]+/i', '', $value) ?? '');
	}

	/**
	 * @return list<string>
	 */
	public static function slugTokens(string $slug): array
	{
		$norm = self::normalizeSlug($slug);
		if ($norm === '') {
			return [];
		}
		$parts = \preg_split('/_+/', $norm, -1, \PREG_SPLIT_NO_EMPTY);

		return $parts !== false ? \array_values($parts) : [];
	}

	/**
	 * @return list<string>
	 */
	public static function phraseTokens(string $phrase): array
	{
		$lower = \strtolower(\trim($phrase));
		if ($lower === '') {
			return [];
		}
		$parts = \preg_split('/[^a-z0-9]+/i', $lower, -1, \PREG_SPLIT_NO_EMPTY);

		return $parts !== false ? \array_values(\array_unique($parts)) : [];
	}

	/**
	 * Tokens taken from CPT registration strings (slug + common labels).
	 *
	 * @return array{slug: list<string>, extended: list<string>}
	 */
	public static function postTypeTokenSets(\WP_Post_Type $pt): array
	{
		$slugTok = self::slugTokens($pt->name);
		$extended = $slugTok;
		foreach (self::postTypeLabelStrings($pt) as $str) {
			foreach (self::phraseTokens($str) as $t) {
				$extended[] = $t;
			}
		}
		$extended = \array_values(\array_unique($extended));

		return ['slug' => $slugTok, 'extended' => $extended];
	}

	/**
	 * @return list<string>
	 */
	private static function postTypeLabelStrings(\WP_Post_Type $pt): array
	{
		$out = [];
		$labels = $pt->labels;
		foreach (['name', 'singular_name', 'menu_name', 'add_new_item', 'edit_item'] as $key) {
			if (!isset($labels->{$key})) {
				continue;
			}
			$s = (string) $labels->{$key};
			if ($s !== '') {
				$out[] = $s;
			}
		}
		if (isset($pt->label) && (string) $pt->label !== '') {
			$out[] = (string) $pt->label;
		}

		return $out;
	}

	/**
	 * Whether the CPT slug contains a blocklisted token (underscore-delimited segments).
	 */
	public static function postTypeSlugHasExclusionToken(string $postTypeSlug): bool
	{
		foreach (self::slugTokens($postTypeSlug) as $t) {
			if (\in_array($t, self::POST_TYPE_SLUG_EXCLUSION_TOKENS, true)) {
				return true;
			}
		}

		return false;
	}

	public static function intersectsRoots(array $tokens, array $roots): bool
	{
		foreach ($tokens as $t) {
			if (\in_array($t, $roots, true)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param list<string> $compactForms full compact alphanumeric slugs
	 */
	public static function compactSlugInList(string $slug, array $compactForms): bool
	{
		$c = self::compactAlphanumeric(self::normalizeSlug($slug));

		return $c !== '' && \in_array($c, $compactForms, true);
	}

	// --- Concept roots (slug tokens; normalized hyphen/underscore via slugTokens) ---

	/** @return list<string> */
	public static function developmentSiteRoots(): array
	{
		return [
			'development', 'developments', 'site', 'sites', 'scheme', 'schemes', 'community', 'communities',
		];
	}

	/**
	 * Strong plot/unit signals (slug tokens only). Excludes home/homes — too generic for careers/marketing CPTs.
	 *
	 * @return list<string>
	 */
	public static function plotStrongSlugTokens(): array
	{
		return ['plot', 'plots', 'unit', 'units', 'property', 'properties'];
	}

	/**
	 * @deprecated Use plotStrongSlugTokens(); retained for callers expecting the old name.
	 * @return list<string>
	 */
	public static function plotUnitRoots(): array
	{
		return self::plotStrongSlugTokens();
	}

	/** @return list<string> */
	public static function pipelineModifierRoots(): array
	{
		return [
			'future', 'pipeline', 'upcoming', 'forthcoming', 'planned', 'prelaunch', 'preview', 'coming', 'soon',
		];
	}

	/** @return list<string> */
	public static function pipelineCompactSlugs(): array
	{
		return [
			'futuredevelopment', 'futuredevelopments', 'pipelinedevelopment', 'pipelinesite', 'pipelinesites',
			'upcomingsite', 'upcomingsites', 'upcomingscheme', 'upcomingschemes', 'comingsoon', 'comingsoondevelopment',
		];
	}

	/** @return list<string> */
	public static function houseTypeCompactSlugs(): array
	{
		return [
			'housetype', 'housetypes', 'propertymodel', 'propertymodels',
		];
	}

	public static function postTypeIsPipelineLike(\WP_Post_Type $pt): bool
	{
		if (self::postTypeSlugHasExclusionToken($pt->name)) {
			return false;
		}

		$slugTok = self::slugTokens($pt->name);
		$dev = self::developmentSiteRoots();
		$mod = self::pipelineModifierRoots();
		$plotStrong = self::plotStrongSlugTokens();

		if (self::compactSlugInList($pt->name, self::pipelineCompactSlugs())) {
			return true;
		}

		if (\in_array('coming', $slugTok, true) && \in_array('soon', $slugTok, true)
			&& (self::intersectsRoots($slugTok, $dev) || self::intersectsRoots($slugTok, $plotStrong))) {
			return true;
		}

		$slugHitsDev = self::intersectsRoots($slugTok, $dev);
		$slugHitsMod = self::intersectsRoots($slugTok, $mod);
		if ($slugHitsMod && ($slugHitsDev || self::intersectsRoots($slugTok, $plotStrong))) {
			return true;
		}

		return false;
	}

	public static function postTypeIsHouseTypeModelLike(\WP_Post_Type $pt): bool
	{
		if (self::postTypeSlugHasExclusionToken($pt->name)) {
			return false;
		}

		if (self::compactSlugInList($pt->name, self::houseTypeCompactSlugs())) {
			return true;
		}

		$sets = self::postTypeTokenSets($pt);
		$slugTok = $sets['slug'];
		$ext = $sets['extended'];

		$houseSide = self::intersectsRoots($ext, ['house', 'houses']);
		$typeSide = self::intersectsRoots($ext, ['type', 'types']);
		$propSide = self::intersectsRoots($ext, ['property', 'properties']);
		$modelSide = self::intersectsRoots($ext, ['model', 'models']);

		$pairHouseType = $houseSide && $typeSide;
		$pairPropertyModel = $propSide && $modelSide;
		if (!$pairHouseType && !$pairPropertyModel) {
			return false;
		}

		$slugHouseType = self::intersectsRoots($slugTok, ['house', 'houses', 'type', 'types']);
		$slugPropModel = self::intersectsRoots($slugTok, ['property', 'properties', 'model', 'models']);

		// Slug must carry the pair; labels alone are not enough (same bar as plot-like).
		if ($pairHouseType && !$slugHouseType) {
			return false;
		}
		if ($pairPropertyModel && !$slugPropModel) {
			return false;
		}

		return true;
	}

	public static function postTypeIsDevelopmentSiteLike(\WP_Post_Type $pt): bool
	{
		if (self::postTypeSlugHasExclusionToken($pt->name)) {
			return false;
		}

		if (self::postTypeIsPipelineLike($pt)) {
			return false;
		}

		$slugTok = self::slugTokens($pt->name);

		return self::intersectsRoots($slugTok, self::developmentSiteRoots());
	}

	public static function postTypeIsPlotUnitLike(\WP_Post_Type $pt): bool
	{
		if (self::postTypeSlugHasExclusionToken($pt->name)) {
			return false;
		}

		if (self::postTypeIsPipelineLike($pt)) {
			return false;
		}

		$slugTok = self::slugTokens($pt->name);
		$strong = self::plotStrongSlugTokens();

		return self::intersectsRoots($slugTok, $strong);
	}

	/**
	 * Ordered role for interpretation: pipeline > house type > development > plot.
	 *
	 * @return array{entity_kind: string}|null
	 */
	public static function postTypeInterpretationProfile(\WP_Post_Type $pt): ?array
	{
		if (self::postTypeIsPipelineLike($pt)) {
			return ['entity_kind' => 'pipeline_development_content'];
		}
		if (self::postTypeIsHouseTypeModelLike($pt)) {
			return ['entity_kind' => 'house_type_or_property_model'];
		}
		if (self::postTypeIsDevelopmentSiteLike($pt)) {
			return ['entity_kind' => 'housing_development_site'];
		}
		if (self::postTypeIsPlotUnitLike($pt)) {
			return ['entity_kind' => 'plot_or_unit'];
		}

		return null;
	}

	// --- Taxonomy ---

	/**
	 * @param list<string> $plotLikeSlugs
	 */
	public static function taxonomyAppliesOnlyToPlotLike(\WP_Taxonomy $tax, array $plotLikeSlugs): bool
	{
		if ($plotLikeSlugs === []) {
			return false;
		}
		$plotSet = \array_fill_keys($plotLikeSlugs, true);
		$objects = \is_array($tax->object_type) ? $tax->object_type : [];
		if ($objects === []) {
			return false;
		}
		foreach ($objects as $pt) {
			if (!isset($plotSet[$pt])) {
				return false;
			}
		}

		return true;
	}

	public static function taxonomySlugBlocked(string $slug): bool
	{
		$norm = self::normalizeSlug($slug);

		return \in_array($norm, self::TAXONOMY_SLUG_BLOCKLIST, true);
	}

	/**
	 * Taxonomy slug/label signals for “classifies plots by development/scheme/site”.
	 */
	public static function taxonomySuggestsDevelopmentSchemeClassifier(\WP_Taxonomy $tax): bool
	{
		if (self::taxonomySlugBlocked($tax->name)) {
			return false;
		}

		$slugNorm = self::normalizeSlug($tax->name);
		$slugTok = self::slugTokens($tax->name);
		$roots = self::developmentSiteRoots();

		if (self::intersectsRoots($slugTok, $roots)) {
			return true;
		}

		// Legacy segment pattern: development_type, foo_development_bar
		if (\preg_match('/(^|_)development(s)?($|_)/', $slugNorm) === 1) {
			return true;
		}
		if (\preg_match('/(^|_)scheme(s)?($|_)/', $slugNorm) === 1) {
			return true;
		}
		if (\preg_match('/(^|_)site(s)?($|_)/', $slugNorm) === 1) {
			return true;
		}
		if (\preg_match('/(^|_)communit(y|ies)($|_)/', $slugNorm) === 1) {
			return true;
		}

		$labelHaystack = '';
		$labels = $tax->labels;
		foreach (['name', 'singular_name', 'menu_name', 'search_items'] as $k) {
			if (isset($labels->{$k})) {
				$labelHaystack .= ' ' . (string) $labels->{$k};
			}
		}
		if (isset($tax->label)) {
			$labelHaystack .= ' ' . (string) $tax->label;
		}
		$labelTok = self::phraseTokens($labelHaystack);
		if (self::intersectsRoots($labelTok, $roots)) {
			// Slug must carry an explicit token (no substring tricks on “website” → “site”).
			$structural = \array_merge(
				$roots,
				['dev', 'hb', 'cpt', 'plot', 'plots', 'unit', 'units', 'location', 'locations', 'area', 'areas', 'region', 'regions']
			);

			return self::intersectsRoots($slugTok, $structural);
		}

		return false;
	}
}
