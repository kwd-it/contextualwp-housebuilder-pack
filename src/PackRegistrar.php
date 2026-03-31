<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack;

final class PackRegistrar
{
	/**
	 * Public pack slug passed to ContextualWP (see core Registry::normalise_and_validate()).
	 */
	public const SECTOR_SLUG = 'housebuilder';

	/**
	 * Registers this sector pack with ContextualWP core.
	 *
	 * @see https://github.com/kwd-it/contextualwp/blob/main/includes/SectorPacks/Registry.php
	 */
	public function register(): void
	{
		if (!\function_exists(Compatibility::CORE_REGISTRATION_FN)) {
			return;
		}

		$fn = Compatibility::CORE_REGISTRATION_FN;
		$fn($this->buildRegistrationConfig());
	}

	/**
	 * Metadata for contextualwp_register_sector_pack( array $meta ): bool.
	 *
	 * Required keys: slug, name, version.
	 * Optional: description, author or vendor, requires_contextualwp, settings_url.
	 * Core strips any other keys during normalisation.
	 *
	 * @return array<string, mixed>
	 */
	public function buildRegistrationConfig(): array
	{
		$version = \defined('CONTEXTUALWP_HOUSEBUILDER_PACK_VERSION')
			? (string) \constant('CONTEXTUALWP_HOUSEBUILDER_PACK_VERSION')
			: '0.1.0';

		$minCore = \defined('CONTEXTUALWP_HOUSEBUILDER_PACK_MIN_CONTEXTUALWP_VERSION')
			? (string) \constant('CONTEXTUALWP_HOUSEBUILDER_PACK_MIN_CONTEXTUALWP_VERSION')
			: '1.1.0';

		return [
			'slug' => self::SECTOR_SLUG,
			'name' => \__('Housebuilder', 'contextualwp-housebuilder-pack'),
			'version' => $version,
			'description' => \__(
				'Reference sector pack scaffold for housebuilder-specific ContextualWP extensions.',
				'contextualwp-housebuilder-pack'
			),
			'author' => 'Kirk Johnston / KWD-IT',
			'requires_contextualwp' => $minCore,
		];
	}
}
