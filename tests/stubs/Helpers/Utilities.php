<?php

declare(strict_types=1);

namespace ContextualWP\Helpers;

/**
 * PHPUnit stub for ContextualWP core Utilities (safe modified-author label only).
 */
final class Utilities
{
	/** @var mixed */
	public static mixed $mockReturn = null;

	public static ?\WP_Post $lastPostArg = null;

	/**
	 * @param \WP_Post|int $post
	 */
	public static function get_safe_modified_author_display_name($post): ?string
	{
		self::$lastPostArg = $post instanceof \WP_Post ? $post : null;

		$value = self::$mockReturn;
		if ($value === null) {
			return null;
		}

		return \is_string($value) ? $value : null;
	}

	public static function reset(): void
	{
		self::$mockReturn = null;
		self::$lastPostArg = null;
	}
}
