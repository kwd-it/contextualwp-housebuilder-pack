<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack;

final class Compatibility
{
	/**
	 * The only guaranteed integration point we assume exists in ContextualWP core.
	 */
	public const CORE_REGISTRATION_FN = 'contextualwp_register_sector_pack';

	/**
	 * The version constant ContextualWP core defines from its plugin header.
	 *
	 * Verified against the core versions this pack supports (>= 1.3.3). When
	 * the constant is absent the version check stays best-effort (see below).
	 */
	public const CORE_VERSION_CONST = 'CONTEXTUALWP_VERSION';

	public static function isCoreAvailable(): bool
	{
		return \function_exists(self::CORE_REGISTRATION_FN);
	}

	/**
	 * Best-effort: returns ContextualWP core version if it is exposed.
	 */
	public static function getCoreVersion(): ?string
	{
		if (\defined(self::CORE_VERSION_CONST)) {
			$version = (string) \constant(self::CORE_VERSION_CONST);
			return $version !== '' ? $version : null;
		}

		return null;
	}

	/**
	 * Checks minimum ContextualWP core version if the version can be detected.
	 * If the core version cannot be detected, this returns true as a deliberate
	 * best-effort assumption, so the pack degrades gracefully rather than
	 * blocking on a core build that does not expose its version.
	 */
	public static function isCoreCompatible(string $minimumVersion): bool
	{
		$coreVersion = self::getCoreVersion();

		if ($coreVersion === null) {
			return true;
		}

		return \version_compare($coreVersion, $minimumVersion, '>=');
	}
}

