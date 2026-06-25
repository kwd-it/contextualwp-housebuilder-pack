<?php

declare(strict_types=1);

if (!\class_exists('WP_Post', false)) {
	/**
	 * Minimal WP_Post stand-in for PHPUnit (plot mapper tests only).
	 */
	class WP_Post
	{
		public int $ID = 0;

		public string $post_type = '';

		public string $post_status = '';

		public string $post_modified_gmt = '';

		public string $post_content = '';
	}
}
