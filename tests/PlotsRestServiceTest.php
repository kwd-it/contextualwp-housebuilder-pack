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

	public function testQueryOrdersByModifiedThenIdForStablePagination(): void
	{
		$args = PlotsRestService::queryArgs(['plot'], 2, 100);

		$this->assertSame(['modified' => 'DESC', 'ID' => 'DESC'], $args['orderby']);
		$this->assertArrayNotHasKey('order', $args);
		$this->assertSame(['plot'], $args['post_type']);
		$this->assertSame('publish', $args['post_status']);
		$this->assertSame(100, $args['posts_per_page']);
		$this->assertSame(2, $args['paged']);
		$this->assertTrue($args['no_found_rows']);
		$this->assertTrue($args['ignore_sticky_posts']);
	}
}
