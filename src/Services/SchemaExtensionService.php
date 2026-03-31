<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

/**
 * Editor-safe semantic hints for ACF fields (keyword rules only; no field-value inspection).
 */
final class SchemaExtensionService
{
	/**
	 * Keywords by semantic group (lowercase substrings matched against name, label, instructions).
	 *
	 * @var array<string, list<string>>
	 */
	private const SEMANTIC_KEYWORDS = [
		'pricing' => [
			'price', 'cost', 'fee', 'fees', 'deposit', 'reservation', 'mortgage', 'pcm', 'per week', 'per month',
			'weekly', 'monthly', 'affordability', 'equity', 'shared ownership', 'part buy', 'rent', 'service charge',
			'stamp duty', 'help to buy',
		],
		'availability' => [
			'available', 'availability', 'status', 'plot status', 'released', 'reserved', 'sold', 'coming soon',
			'launch', 'build stage', 'completion',
		],
		'specification' => [
			'bedroom', 'bedrooms', 'bathroom', 'bathrooms', 'sq ft', 'sqft', 'square foot', 'floor plan',
			'tenure', 'freehold', 'leasehold', 'epc', 'heating', 'parking', 'garage', 'garden', 'storey', 'stories',
			'dimensions', 'sq m', 'sqm',
		],
		'location' => [
			'address', 'postcode', 'post code', 'map', 'google map', 'coordinates', 'latitude', 'longitude',
			'directions', 'local area', 'nearby', 'location',
		],
		'media' => [
			'image', 'gallery', 'photo', 'video', 'virtual tour', 'tour', 'hero', 'carousel', 'slider',
		],
		'downloads' => [
			'brochure', 'download', 'pdf', 'leaflet', 'document pack',
		],
		'marketing_sales' => [
			'headline', 'strapline', 'cta', 'call to action', 'banner', 'promotion', 'incentive', 'offer',
			'sales', 'marketing',
		],
		'status' => [
			'featured', 'highlight', 'archive', 'archived', 'visibility', 'published date', 'expiry',
		],
	];

	public function register(): void
	{
		\add_filter('contextualwp_acf_schema_field_groups', [$this, 'filterAcfFieldGroups'], 10, 1);
	}

	/**
	 * @param array<int, array<string, mixed>> $fieldGroups
	 * @return array<int, array<string, mixed>>
	 */
	public function filterAcfFieldGroups(array $fieldGroups): array
	{
		foreach ($fieldGroups as $i => $group) {
			$fields = $group['fields'] ?? null;
			if (!\is_array($fields)) {
				continue;
			}

			$groupsSeen = [];
			foreach ($fields as $j => $field) {
				if (!\is_array($field)) {
					continue;
				}
				$semantic = $this->inferFieldSemantics($field);
				if ($semantic === null) {
					continue;
				}
				$fields[$j]['semantic'] = $semantic;
				$groupsSeen[$semantic['group']] = true;
			}

			$fieldGroups[$i]['fields'] = $fields;
			if ($groupsSeen !== []) {
				$keys = \array_keys($groupsSeen);
				\sort($keys, \SORT_STRING);
				$fieldGroups[$i]['semantic_groups'] = $keys;
			}
		}

		return $fieldGroups;
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array{group: string, basis: string, keywords_matched: list<string>}|null
	 */
	private function inferFieldSemantics(array $field): ?array
	{
		$name = isset($field['name']) ? \strtolower((string) $field['name']) : '';
		$label = isset($field['label']) ? \strtolower((string) $field['label']) : '';
		$instr = isset($field['instructions']) && \is_string($field['instructions'])
			? \strtolower($field['instructions'])
			: '';
		$haystack = \trim($name . ' ' . $label . ' ' . $instr);
		if ($haystack === '') {
			return null;
		}

		$scores = [];
		$matchedByGroup = [];

		foreach (self::SEMANTIC_KEYWORDS as $group => $keywords) {
			$scores[$group] = 0;
			$matchedByGroup[$group] = [];

			foreach ($keywords as $kw) {
				if (\str_contains($haystack, $kw)) {
					++$scores[$group];
					$matchedByGroup[$group][] = $kw;
				}
			}
		}

		\arsort($scores, \SORT_NUMERIC);
		$top = \array_key_first($scores);
		$topScore = $top !== null ? (int) $scores[$top] : 0;

		if ($topScore < 1 || $top === null) {
			return null;
		}

		// Ambiguous if another group ties for the top score.
		$tied = 0;
		foreach ($scores as $g => $s) {
			if ((int) $s === $topScore) {
				++$tied;
			}
		}
		if ($tied > 1) {
			return null;
		}

		return [
			'group' => $top,
			'basis' => 'keyword_substrings',
			'keywords_matched' => \array_values(\array_unique($matchedByGroup[$top])),
		];
	}
}
