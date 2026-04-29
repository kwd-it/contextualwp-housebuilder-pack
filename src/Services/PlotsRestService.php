<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

use ContextualWP\HousebuilderPack\Compatibility;

/**
 * Read-only REST surface for published plot-like posts (public monitoring fields only).
 *
 * @see \ContextualWP\Endpoints\ACF_Schema::check_permissions() (ContextualWP core uses edit_posts for editor-facing GET routes)
 */
final class PlotsRestService
{
	private const ROUTE_NAMESPACE = 'contextualwp-housebuilder/v1';

	private const ROUTE_PATH = '/plots';

	public function register(): void
	{
		\add_action('rest_api_init', [$this, 'registerRoute'], 10);
	}

	public function registerRoute(): void
	{
		if (!Compatibility::isCoreAvailable()) {
			return;
		}

		\register_rest_route(self::ROUTE_NAMESPACE, self::ROUTE_PATH, [
			'methods' => \WP_REST_Server::READABLE,
			'callback' => [$this, 'handleRequest'],
			'permission_callback' => [$this, 'checkPermissions'],
			'args' => [
				'page' => [
					'description' => \__('Current page of results.', 'contextualwp-housebuilder-pack'),
					'type' => 'integer',
					'default' => 1,
					'minimum' => 1,
					'sanitize_callback' => static fn ($v): int => \max(1, (int) $v),
				],
				'per_page' => [
					'description' => \__('Maximum items per page (capped at 500). Clients may also send limit when per_page is omitted from the query string. No total count is returned; stop when a page has fewer items than per_page.', 'contextualwp-housebuilder-pack'),
					'type' => 'integer',
					'default' => PlotsRestQueryLimits::DEFAULT_PER_PAGE,
					'minimum' => 1,
					'maximum' => PlotsRestQueryLimits::MAX_PER_PAGE,
					'sanitize_callback' => static fn ($v): int => \max(1, \min(PlotsRestQueryLimits::MAX_PER_PAGE, (int) $v)),
				],
			],
		]);
	}

	public function checkPermissions(): bool
	{
		$capability = \apply_filters('contextualwp_housebuilder_rest_plots_capability', 'edit_posts');
		$capability = \is_string($capability) && $capability !== '' ? $capability : 'edit_posts';

		return \current_user_can($capability);
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handleRequest(\WP_REST_Request $request)
	{
		$plotTypes = SiteStructureHints::plotLikePublicPostTypeSlugs();
		if ($plotTypes === []) {
			return \rest_ensure_response([]);
		}

		$page = (int) $request->get_param('page');
		$perPageFallback = (int) $request->get_param('per_page');
		$resolved = PlotsRestQueryLimits::resolvePageAndPerPage(
			$page,
			$perPageFallback,
			$request->get_query_params()
		);
		$page = $resolved['page'];
		$perPage = $resolved['per_page'];

		$metaKeys = $this->mergeMetaKeyMap(PlotDatasetMapper::defaultMetaKeyMap());

		$query = new \WP_Query([
			'post_type' => $plotTypes,
			'post_status' => 'publish',
			'orderby' => 'modified',
			'order' => 'DESC',
			'posts_per_page' => $perPage,
			'paged' => $page,
			'no_found_rows' => true,
			'ignore_sticky_posts' => true,
		]);

		$out = [];
		foreach ($query->posts as $post) {
			if (!$post instanceof \WP_Post) {
				continue;
			}
			$out[] = PlotDatasetMapper::mapPost($post, $metaKeys);
		}

		return \rest_ensure_response($out);
	}

	/**
	 * @param array<string, list<string>> $defaults
	 * @return array<string, list<string>>
	 */
	private function mergeMetaKeyMap(array $defaults): array
	{
		$custom = \apply_filters('contextualwp_housebuilder_plot_meta_key_candidates', $defaults);
		if (!\is_array($custom)) {
			return $defaults;
		}

		$out = $defaults;
		foreach ($custom as $logical => $keys) {
			if (!\is_string($logical) || $logical === '' || !\is_array($keys)) {
				continue;
			}
			$clean = [];
			foreach ($keys as $k) {
				if (!\is_string($k) && !\is_int($k)) {
					continue;
				}
				$s = \trim((string) $k);
				if ($s !== '') {
					$clean[] = $s;
				}
			}
			if ($clean !== []) {
				$out[$logical] = $clean;
			}
		}

		return $out;
	}
}
