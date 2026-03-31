<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack;

final class Plugin
{
	private PackRegistrar $registrar;

	public function __construct(PackRegistrar $registrar)
	{
		$this->registrar = $registrar;
	}

	public function registerHooks(): void
	{
		// Keep bootstrap small: run a single guarded boot step.
		// Run after the main plugin file's Bootstrap init callback.
		\add_action('plugins_loaded', [$this, 'boot'], 30);
	}

	public function boot(): void
	{
		if (!Compatibility::isCoreAvailable()) {
			$this->maybeAdminNoticeMissingCore();
			return;
		}

		$min = \defined('CONTEXTUALWP_HOUSEBUILDER_PACK_MIN_CONTEXTUALWP_VERSION')
			? (string) \constant('CONTEXTUALWP_HOUSEBUILDER_PACK_MIN_CONTEXTUALWP_VERSION')
			: '1.1.0';

		if (!Compatibility::isCoreCompatible($min)) {
			$this->maybeAdminNoticeIncompatibleCore($min, Compatibility::getCoreVersion());
			return;
		}

		$this->registrar->register();
	}

	private function maybeAdminNoticeMissingCore(): void
	{
		if (!\is_admin()) {
			return;
		}

		\add_action('admin_notices', static function (): void {
			if (!\current_user_can('activate_plugins')) {
				return;
			}

			echo '<div class="notice notice-info"><p>'
				. \esc_html__('ContextualWP Housebuilder Pack is active but ContextualWP core is not available. Install/activate ContextualWP (v1.1+).', 'contextualwp-housebuilder-pack')
				. '</p></div>';
		});
	}

	private function maybeAdminNoticeIncompatibleCore(string $min, ?string $detected): void
	{
		if (!\is_admin()) {
			return;
		}

		\add_action('admin_notices', static function () use ($min, $detected): void {
			if (!\current_user_can('activate_plugins')) {
				return;
			}

			$detectedText = $detected !== null ? $detected : \__('unknown', 'contextualwp-housebuilder-pack');

			echo '<div class="notice notice-warning"><p>'
				. \esc_html(
					\sprintf(
						/* translators: 1: minimum required version, 2: detected core version */
						__('ContextualWP Housebuilder Pack requires ContextualWP core %1$s or newer. Detected: %2$s.', 'contextualwp-housebuilder-pack'),
						$min,
						$detectedText
					)
				)
				. '</p></div>';
		});
	}
}

