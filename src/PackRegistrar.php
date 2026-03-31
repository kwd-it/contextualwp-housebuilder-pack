<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack;

use ContextualWP\HousebuilderPack\Services\InterpretationService;
use ContextualWP\HousebuilderPack\Services\RelationshipService;
use ContextualWP\HousebuilderPack\Services\SchemaExtensionService;

final class PackRegistrar
{
	public const SECTOR_SLUG = 'housebuilder';

	/**
	 * Registers this sector pack with ContextualWP core.
	 *
	 * Integration point assumption (to verify in ContextualWP core v1.1+):
	 * - `contextualwp_register_sector_pack( array $pack ): void`
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
	 * Placeholder configuration shape intended for a future stable pack API.
	 * Keep this minimal and safe for a public reference implementation.
	 *
	 * @return array<string, mixed>
	 */
	public function buildRegistrationConfig(): array
	{
		$version = \defined('CONTEXTUALWP_HOUSEBUILDER_PACK_VERSION')
			? (string) \constant('CONTEXTUALWP_HOUSEBUILDER_PACK_VERSION')
			: '0.1.0';

		return [
			'sector' => [
				'slug' => self::SECTOR_SLUG,
				'label' => 'Housebuilder',
			],
			'pack' => [
				'slug' => 'contextualwp-housebuilder-pack',
				'version' => $version,
			],
			'services' => [
				'schema_extension' => SchemaExtensionService::class,
				'relationships' => RelationshipService::class,
				'interpretation' => InterpretationService::class,
			],
			'meta' => [
				// Intentionally empty: future reserved keys (e.g., docs URL, schema version).
			],
		];
	}
}

