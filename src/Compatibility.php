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
	 * Placeholder: if ContextualWP exposes a version constant, we will read it.
	 *
	 * This should be verified against ContextualWP core (v1.1+).
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
	 * If the core version cannot be detected, this currently returns true as a
	 * best-effort assumption.
	 *
	 * Assumption to verify (ContextualWP core v1.1+):
	 * - Core exposes a reliable version API (constant or function) for strict checks.
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

