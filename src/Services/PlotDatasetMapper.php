<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

/**
 * Maps plot-like posts to a stable public monitoring shape for REST consumers.
 *
 * Site-specific field names are resolved via ordered meta-key candidates and filters,
 * not hard-coded single keys inside the route callback.
 */
final class PlotDatasetMapper
{
	/**
	 * Default post meta keys to try, in order, per logical field.
	 *
	 * @return array<string, list<string>>
	 */
	public static function defaultMetaKeyMap(): array
	{
		return [
			'status' => [
				'plot_status',
				'availability',
				'availability_status',
				'status',
				'property_status',
				'sales_status',
			],
			'price' => [
				'price',
				'asking_price',
				'guide_price',
				'plot_price',
				'property_price',
			],
			'bedrooms' => [
				'bedrooms',
				'bedroom',
				'beds',
				'number_of_bedrooms',
				'bedrooms_count',
			],
			'house_type_ref' => [
				'house_type',
				'house_type_id',
				'property_model',
				'property_model_id',
				'model',
				'model_id',
				'product',
				'product_id',
			],
			'development_ref' => [
				'development',
				'development_id',
				'site',
				'site_id',
				'scheme',
				'scheme_id',
			],
		];
	}

	/**
	 * @param array<string, list<string>> $metaKeys
	 * @return array<string, mixed>
	 */
	public static function mapPost(\WP_Post $post, array $metaKeys): array
	{
		$postType = $post->post_type;
		$postId = (int) $post->ID;

		$statusRaw = self::firstScalarMeta($postId, $metaKeys['status'] ?? []);
		$priceRaw = self::firstScalarMeta($postId, $metaKeys['price'] ?? []);
		$bedroomsRaw = self::firstScalarMeta($postId, $metaKeys['bedrooms'] ?? []);
		$houseRefRaw = self::firstScalarMeta($postId, $metaKeys['house_type_ref'] ?? []);
		$devRefRaw = self::firstScalarMeta($postId, $metaKeys['development_ref'] ?? []);

		$development = self::resolveDevelopmentLabel($post, $devRefRaw);
		$houseType = self::resolveHouseTypeLabel($houseRefRaw);

		return [
			'id' => $postType . '-' . $postId,
			'wp_id' => $postId,
			'title' => (string) \get_the_title($post),
			'status' => self::normalizeStatusString($statusRaw),
			'price' => self::coercePriceNumber($priceRaw),
			'bedrooms' => self::coerceNonNegativeInt($bedroomsRaw),
			'development' => $development,
			'house_type' => $houseType,
			'url' => self::safePermalink($post),
			'last_updated' => self::modifiedIso8601Utc($post),
		];
	}

	private static function safePermalink(\WP_Post $post): ?string
	{
		$url = \get_permalink($post);
		if (!\is_string($url) || $url === '') {
			return null;
		}

		return $url;
	}

	private static function modifiedIso8601Utc(\WP_Post $post): ?string
	{
		if (\function_exists('get_post_datetime')) {
			$dt = \get_post_datetime($post, 'modified', 'gmt');
			if ($dt instanceof \DateTimeInterface) {
				return $dt->format(\DATE_ATOM);
			}
		}

		$gmt = isset($post->post_modified_gmt) ? (string) $post->post_modified_gmt : '';
		if ($gmt === '' || $gmt === '0000-00-00 00:00:00') {
			return null;
		}

		$ts = \strtotime($gmt . ' UTC');

		return $ts !== false ? \gmdate('c', $ts) : null;
	}

	/**
	 * @param list<string> $keys
	 */
	private static function firstScalarMeta(int $postId, array $keys): mixed
	{
		foreach ($keys as $key) {
			if ($key === '') {
				continue;
			}
			$val = \get_post_meta($postId, $key, true);
			if ($val === '' || $val === null || $val === false) {
				continue;
			}
			if (\is_array($val) && $val === []) {
				continue;
			}

			return $val;
		}

		return null;
	}

	private static function normalizeStatusString(mixed $raw): ?string
	{
		if ($raw === null) {
			return null;
		}
		if (\is_numeric($raw)) {
			$s = (string) $raw;

			return $s !== '' ? \strtolower(\trim($s)) : null;
		}
		if (!\is_string($raw)) {
			return null;
		}
		$t = \strtolower(\trim($raw));

		return $t !== '' ? $t : null;
	}

	/**
	 * @return int|float|null
	 */
	private static function coercePriceNumber(mixed $raw): int|float|null
	{
		$n = self::coerceUnsignedFloat($raw);
		if ($n === null) {
			return null;
		}
		$rounded = (int) \round($n);

		return \abs($n - (float) $rounded) < 0.01 ? $rounded : $n;
	}

	private static function coerceUnsignedFloat(mixed $raw): ?float
	{
		if ($raw === null || $raw === '' || $raw === false) {
			return null;
		}
		if (\is_int($raw) || \is_float($raw)) {
			$n = (float) $raw;

			return $n >= 0 ? $n : null;
		}
		if (\is_string($raw)) {
			$clean = \preg_replace('/[^\d.]/', '', $raw);
			if ($clean === null || $clean === '') {
				return null;
			}
			$n = (float) $clean;

			return $n >= 0 ? $n : null;
		}

		return null;
	}

	private static function coerceNonNegativeInt(mixed $raw): ?int
	{
		if ($raw === null || $raw === '' || $raw === false) {
			return null;
		}
		if (\is_int($raw)) {
			return $raw >= 0 ? $raw : null;
		}
		if (\is_float($raw)) {
			$i = (int) \round($raw);

			return $i >= 0 ? $i : null;
		}
		if (\is_string($raw)) {
			$digits = \preg_replace('/\D/', '', $raw);
			if ($digits === null || $digits === '') {
				return null;
			}
			$i = (int) $digits;

			return $i >= 0 ? $i : null;
		}

		return null;
	}

	private static function resolveDevelopmentLabel(\WP_Post $post, mixed $devRefRaw): ?string
	{
		$fromTax = self::developmentFromClassifierTaxonomy($post);
		if ($fromTax !== null && $fromTax !== '') {
			return $fromTax;
		}

		return self::publishedLinkedPostTitle($devRefRaw);
	}

	private static function developmentFromClassifierTaxonomy(\WP_Post $post): ?string
	{
		$taxonomies = \get_object_taxonomies($post->post_type, 'objects');
		if (!\is_array($taxonomies)) {
			return null;
		}

		foreach ($taxonomies as $tax) {
			if (!$tax instanceof \WP_Taxonomy) {
				continue;
			}
			if (!SiteStructureHints::isPlotDevelopmentClassifierTaxonomy($tax)) {
				continue;
			}
			$terms = \wp_get_post_terms($post->ID, $tax->name, ['fields' => 'all']);
			if (!\is_array($terms) || $terms === []) {
				continue;
			}
			$first = $terms[0];
			if ($first instanceof \WP_Term) {
				$name = \trim((string) $first->name);

				return $name !== '' ? $name : null;
			}
		}

		return null;
	}

	private static function resolveHouseTypeLabel(mixed $houseRefRaw): ?string
	{
		$fromLink = self::publishedLinkedPostTitle($houseRefRaw);
		if ($fromLink !== null) {
			return $fromLink;
		}

		return null;
	}

	private static function publishedLinkedPostTitle(mixed $ref): ?string
	{
		if ($ref === null || $ref === '' || $ref === false) {
			return null;
		}

		$postId = null;
		if (\is_int($ref) || \is_float($ref)) {
			$postId = (int) $ref;
		} elseif (\is_string($ref) && \ctype_digit($ref)) {
			$postId = (int) $ref;
		} elseif (\is_array($ref)) {
			$first = $ref[0] ?? null;
			if (\is_numeric($first)) {
				$postId = (int) $first;
			}
		}

		if ($postId === null || $postId <= 0) {
			if (\is_string($ref)) {
				$t = \trim($ref);

				return $t !== '' ? $t : null;
			}

			return null;
		}

		$linked = \get_post($postId);
		if (!$linked instanceof \WP_Post) {
			return null;
		}
		if ($linked->post_status !== 'publish') {
			return null;
		}
		$title = \get_the_title($linked);
		$title = \is_string($title) ? \trim($title) : '';

		return $title !== '' ? $title : null;
	}
}
