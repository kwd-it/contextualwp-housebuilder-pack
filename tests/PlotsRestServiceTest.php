<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Tests;

use ContextualWP\HousebuilderPack\Services\PlotsRestService;
use PHPUnit\Framework\TestCase;

/**
 * Plots REST route is authenticated (not public).
 */
final class PlotsRestServiceTest extends TestCase
{
	public function testCheckPermissionsRequiresCapabilityByDefault(): void
	{
		$checkedCapability = null;
		$GLOBALS['contextualwp_housebuilder_test_current_user_can'] = static function (string $cap) use (&$checkedCapability): bool {
			$checkedCapability = $cap;

			return false;
		};

		$service = new PlotsRestService();

		$this->assertFalse($service->checkPermissions());
		$this->assertSame('edit_posts', $checkedCapability);
	}
}
