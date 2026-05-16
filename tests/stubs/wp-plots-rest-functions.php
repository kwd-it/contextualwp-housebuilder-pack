<?php

declare(strict_types=1);

/**
 * Global WordPress function stubs for PlotsRestService permission tests.
 */
if (!\function_exists('current_user_can')) {
	function current_user_can(string $capability): bool
	{
		$fn = $GLOBALS['contextualwp_housebuilder_test_current_user_can'] ?? null;

		return \is_callable($fn) ? $fn($capability) : false;
	}
}

if (!\function_exists('apply_filters')) {
	/**
	 * @param mixed $value
	 * @return mixed
	 */
	function apply_filters(string $hook, $value, mixed ...$args)
	{
		return $value;
	}
}
