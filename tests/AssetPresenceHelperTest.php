<?php

declare(strict_types=1);

namespace ContextualWP\HousebuilderPack\Tests;

use ContextualWP\HousebuilderPack\Services\AssetPresenceHelper;
use PHPUnit\Framework\TestCase;

/**
 * Conservative value-shape detection for asset presence signals.
 *
 * Values reaching the video/image helpers come from logically-typed meta key candidates, so any
 * value that indicates presence must be recognised regardless of how the site stores it.
 */
final class AssetPresenceHelperTest extends TestCase
{
	/**
	 * @dataProvider providePresentVideoValues
	 *
	 * @param mixed $raw
	 */
	public function testVideoValuesAreRecognisedRegardlessOfShape($raw): void
	{
		$this->assertTrue(AssetPresenceHelper::metaValueIndicatesVideo($raw));
	}

	/**
	 * @return array<string, array{0: mixed}>
	 */
	public static function providePresentVideoValues(): array
	{
		return [
			'provider url' => ['https://vimeo.com/123456789'],
			'bare numeric id string' => ['824804225'],
			'self-hosted file url' => ['https://cdn.example.test/intro.mp4'],
			'embed html string' => ['<iframe src="https://player.example.test/v/1"></iframe>'],
			'attachment id int' => [4455],
			'oembed array' => [['url' => 'https://vimeo.com/55', 'value' => '<iframe></iframe>']],
			'nested group with src' => [['src' => 'https://cdn.example.test/intro.webm']],
			'acf media array' => [['ID' => 4455, 'mime_type' => 'video/mp4']],
		];
	}

	/**
	 * @dataProvider provideAbsentValues
	 *
	 * @param mixed $raw
	 */
	public function testAbsentVideoValuesAreNotPresent($raw): void
	{
		$this->assertFalse(AssetPresenceHelper::metaValueIndicatesVideo($raw));
	}

	/**
	 * @return array<string, array{0: mixed}>
	 */
	public static function provideAbsentValues(): array
	{
		return [
			'empty string' => [''],
			'whitespace string' => ['   '],
			'zero string' => ['0'],
			'zero int' => [0],
			'null' => [null],
			'false' => [false],
			'empty array' => [[]],
		];
	}

	public function testHasPresentMetaReturnsNullWhenNoKeysConfigured(): void
	{
		$this->assertNull(AssetPresenceHelper::hasPresentMeta(1, [], 'video'));
	}
}
