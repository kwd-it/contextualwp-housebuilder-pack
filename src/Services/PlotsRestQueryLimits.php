<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

/**
 * Resolves {@see PlotsRestService} pagination from REST params and raw query string.
 *
 * `per_page` in the query string wins over `limit` when both are present (WordPress REST collection convention).
 * When neither is present, the already-sanitised `per_page` value from the REST request (schema default) is used.
 */
final class PlotsRestQueryLimits
{
	public const DEFAULT_PER_PAGE = 500;

	public const MAX_PER_PAGE = 500;

	/**
	 * @param array<string, mixed> $queryParams {@see \WP_REST_Request::get_query_params()}
	 * @return array{page: int, per_page: int}
	 */
	public static function resolvePageAndPerPage(int $sanitisedPage, int $sanitisedPerPageFallback, array $queryParams): array
	{
		$page = \max(1, $sanitisedPage);

		if (\array_key_exists('per_page', $queryParams)) {
			$perPage = self::parseCappedPerPage(self::firstQueryValue($queryParams['per_page']));
		} elseif (\array_key_exists('limit', $queryParams)) {
			$perPage = self::parseCappedPerPage(self::firstQueryValue($queryParams['limit']));
		} else {
			$perPage = \max(1, \min(self::MAX_PER_PAGE, $sanitisedPerPageFallback));
		}

		return [
			'page' => $page,
			'per_page' => $perPage,
		];
	}

	private static function firstQueryValue(mixed $value): string
	{
		if (\is_array($value)) {
			$value = $value[0] ?? '';
		}

		return \trim((string) $value);
	}

	/**
	 * Non-numeric or empty values fall back to {@see self::DEFAULT_PER_PAGE} so invalid `limit` / `per_page`
	 * query input does not produce surprising page sizes.
	 */
	private static function parseCappedPerPage(string $raw): int
	{
		if ($raw === '' || !\is_numeric($raw)) {
			return self::DEFAULT_PER_PAGE;
		}

		$n = (int) $raw;

		return \max(1, \min(self::MAX_PER_PAGE, $n));
	}
}
