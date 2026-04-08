<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack;

use ContextualWP\HousebuilderPack\Services\InterpretationService;
use ContextualWP\HousebuilderPack\Services\RelationshipService;
use ContextualWP\HousebuilderPack\Services\SchemaExtensionService;

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
	 * Subscribes to ContextualWP filter hooks (manifest schema, REST schema, ACF schema).
	 * Runs after core pack registration; safe no-op if core is unavailable.
	 */
	public function registerExtensions(): void
	{
		if (!Compatibility::isCoreAvailable()) {
			return;
		}

		(new RelationshipService())->register();
		(new InterpretationService())->register();
		(new SchemaExtensionService())->register();
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
			: '0.3.0';

		$minCore = \defined('CONTEXTUALWP_HOUSEBUILDER_PACK_MIN_CONTEXTUALWP_VERSION')
			? (string) \constant('CONTEXTUALWP_HOUSEBUILDER_PACK_MIN_CONTEXTUALWP_VERSION')
			: '1.1.0';

		return [
			'slug' => self::SECTOR_SLUG,
			'name' => \__('Housebuilder', 'contextualwp-housebuilder-pack'),
			'version' => $version,
			'description' => \__(
				'Housebuilder-oriented relationship hints, schema interpretation, and conservative ACF semantic tagging for ContextualWP.',
				'contextualwp-housebuilder-pack'
			),
			'author' => 'Kirk Johnston / KWD-IT',
			'requires_contextualwp' => $minCore,
		];
	}
}
