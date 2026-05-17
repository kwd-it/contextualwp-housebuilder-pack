<?php

declare(strict_types=1);

/**
 * Global WordPress function stubs for PlotDatasetMapper::mapPost() PHPUnit coverage.
 */
if (!\function_exists('get_the_title')) {
	/** @param \WP_Post|int $post */
	function get_the_title($post): string
	{
		$titles = $GLOBALS['contextualwp_housebuilder_test_post_titles'] ?? [];
		if (\is_array($titles)) {
			$id = $post instanceof \WP_Post ? $post->ID : (int) $post;
			if (isset($titles[$id]) && \is_string($titles[$id])) {
				return $titles[$id];
			}
		}

		return 'Plot title';
	}
}

if (!\function_exists('get_post_meta')) {
	/**
	 * @param mixed $single
	 * @return mixed
	 */
	function get_post_meta(int $postId, string $key, bool $single = false)
	{
		$map = $GLOBALS['contextualwp_housebuilder_test_post_meta'] ?? [];
		if (!\is_array($map)) {
			return $single ? '' : [];
		}
		$postMeta = $map[$postId] ?? [];
		if (!\is_array($postMeta) || !\array_key_exists($key, $postMeta)) {
			return $single ? '' : [];
		}

		return $single ? $postMeta[$key] : [$postMeta[$key]];
	}
}

if (!\function_exists('get_post')) {
	/**
	 * @return \WP_Post|null
	 */
	function get_post(int $postId)
	{
		$map = $GLOBALS['contextualwp_housebuilder_test_posts'] ?? [];
		if (!\is_array($map)) {
			return null;
		}
		$post = $map[$postId] ?? null;

		return $post instanceof \WP_Post ? $post : null;
	}
}

if (!\function_exists('get_permalink')) {
	/** @param \WP_Post|int $post */
	function get_permalink($post): string
	{
		return 'https://example.test/plot/';
	}
}

if (!\function_exists('get_object_taxonomies')) {
	/** @return array<string, mixed> */
	function get_object_taxonomies($objectType, $output = 'names'): array
	{
		return [];
	}
}
