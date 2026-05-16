<?php

declare(strict_types=1);

/**
 * Global WordPress function stubs for PlotDatasetMapper::mapPost() PHPUnit coverage.
 */
if (!\function_exists('get_the_title')) {
	/** @param \WP_Post|int $post */
	function get_the_title($post): string
	{
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
		return $single ? '' : [];
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
