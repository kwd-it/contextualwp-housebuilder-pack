<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Services;

/**
 * Conservative detection of whether post meta values indicate an asset is present.
 *
 * Inspects common WordPress / ACF shapes only; does not load attachment files or post content.
 */
final class AssetPresenceHelper
{
	/**
	 * Whether a meta value indicates any usable media asset (image, file, URL, or attachment id).
	 */
	public static function metaValueIndicatesPresence(mixed $raw): bool
	{
		if ($raw === null || $raw === '' || $raw === false) {
			return false;
		}
		if (\is_int($raw) || \is_float($raw)) {
			return (int) $raw > 0;
		}
		if (\is_string($raw)) {
			return self::stringIndicatesPresence($raw);
		}
		if (!\is_array($raw)) {
			return false;
		}
		if ($raw === []) {
			return false;
		}

		foreach (['ID', 'id', 'url', 'value'] as $key) {
			if (!\array_key_exists($key, $raw)) {
				continue;
			}
			if (self::metaValueIndicatesPresence($raw[$key])) {
				return true;
			}
		}

		if (\array_is_list($raw)) {
			foreach ($raw as $item) {
				if (self::metaValueIndicatesPresence($item)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Whether a meta value stored under a video-oriented field indicates a video asset.
	 */
	public static function metaValueIndicatesVideo(mixed $raw): bool
	{
		if (!self::metaValueIndicatesPresence($raw)) {
			return false;
		}
		if (\is_string($raw)) {
			return self::stringIndicatesVideoUrl($raw);
		}
		if (\is_array($raw)) {
			foreach (['url', 'value', 'oembed', 'embed'] as $key) {
				if (!\array_key_exists($key, $raw)) {
					continue;
				}
				$inner = $raw[$key];
				if (\is_string($inner) && self::stringIndicatesVideoUrl($inner)) {
					return true;
				}
			}
			if (\array_is_list($raw)) {
				foreach ($raw as $item) {
					if (self::metaValueIndicatesVideo($item)) {
						return true;
					}
				}
			}
		}

		// Attachment id or opaque non-URL value on a video-labelled field counts as present.
		return true;
	}

	/**
	 * Whether a meta value stored under an image-oriented field indicates an image asset.
	 */
	public static function metaValueIndicatesImage(mixed $raw): bool
	{
		if (!self::metaValueIndicatesPresence($raw)) {
			return false;
		}
		if (\is_string($raw)) {
			return self::stringIndicatesImageUrl($raw) || self::stringIndicatesPresence($raw);
		}
		if (\is_array($raw)) {
			foreach (['url', 'value', 'sizes'] as $key) {
				if (!\array_key_exists($key, $raw)) {
					continue;
				}
				if (self::metaValueIndicatesImage($raw[$key])) {
					return true;
				}
			}
			if (isset($raw['ID']) || isset($raw['id'])) {
				return true;
			}
			if (\array_is_list($raw)) {
				foreach ($raw as $item) {
					if (self::metaValueIndicatesImage($item)) {
						return true;
					}
				}
			}
		}

		return \is_int($raw) || \is_float($raw);
	}

	/**
	 * @param list<string> $keys
	 */
	public static function firstPresentMetaValue(int $postId, array $keys): mixed
	{
		foreach ($keys as $key) {
			if ($key === '') {
				continue;
			}
			$val = \get_post_meta($postId, $key, true);
			if (self::metaValueIndicatesPresence($val)) {
				return $val;
			}
		}

		return null;
	}

	/**
	 * @param list<string> $keys
	 */
	public static function hasPresentMeta(int $postId, array $keys, string $kind = 'any'): ?bool
	{
		if ($keys === []) {
			return null;
		}

		$found = false;
		foreach ($keys as $key) {
			if ($key === '') {
				continue;
			}
			$val = \get_post_meta($postId, $key, true);
			$present = match ($kind) {
				'video' => self::metaValueIndicatesVideo($val),
				'image' => self::metaValueIndicatesImage($val),
				default => self::metaValueIndicatesPresence($val),
			};
			if ($present) {
				return true;
			}
			$found = true;
		}

		return $found ? false : null;
	}

	private static function stringIndicatesPresence(string $raw): bool
	{
		$trimmed = \trim($raw);

		return $trimmed !== '' && $trimmed !== '0';
	}

	private static function stringIndicatesVideoUrl(string $raw): bool
	{
		$lower = \strtolower(\trim($raw));
		if ($lower === '') {
			return false;
		}

		return \str_contains($lower, 'vimeo.com')
			|| \str_contains($lower, 'youtube.com')
			|| \str_contains($lower, 'youtu.be')
			|| \str_contains($lower, 'player.vimeo.com');
	}

	private static function stringIndicatesImageUrl(string $raw): bool
	{
		$lower = \strtolower(\trim($raw));
		if ($lower === '' || !\preg_match('#^https?://#i', $lower)) {
			return false;
		}

		return (bool) \preg_match('/\.(jpe?g|png|gif|webp|avif|svg)(\?|$)/i', $lower);
	}
}
