<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack;

final class Bootstrap
{
	private Plugin $plugin;

	public function __construct()
	{
		$this->plugin = new Plugin(
			new PackRegistrar()
		);
	}

	public function init(): void
	{
		$this->plugin->registerHooks();
	}
}
