<?php

declare(strict_types=1);

namespace SmgenTimeline;

use RuntimeException;

/**
 * Locates the smgen-categories package this plugin builds its day rollups with.
 */
final class Dependencies
{
	/**
	 * Load the categories autoloader from the first location that holds the package.
	 *
	 * @param string|null $projectRoot Site root whose node_modules may hold a hoisted copy.
	 */
	public static function loadCategories(?string $projectRoot = null): void
	{
		if(class_exists('SmgenCategories\\Categories', false))
		{
			return;
		}
		$pluginRoot = dirname(__DIR__);
		$candidates = [];
		$override = getenv('SMGEN_CATEGORIES_ROOT');
		if(is_string($override) && $override !== '')
		{
			$candidates[] = $override;
		}
		$candidates[] = $pluginRoot . '/node_modules/smgen-categories';
		$candidates[] = dirname($pluginRoot) . '/smgen-categories';
		$directory = $pluginRoot;
		for($depth = 0; $depth < 6; $depth++)
		{
			$parent = dirname($directory);
			if($parent === $directory)
			{
				break;
			}
			$candidates[] = $parent . '/node_modules/smgen-categories';
			$directory = $parent;
		}
		if($projectRoot !== null)
		{
			$candidates[] = rtrim($projectRoot, '/') . '/node_modules/smgen-categories';
		}
		foreach($candidates as $candidate)
		{
			$autoload = $candidate . '/src/autoload.php';
			if(is_file($autoload))
			{
				require_once($autoload);
				return;
			}
		}
		throw new RuntimeException('smgen-timeline requires smgen-categories; install it beside this package or set SMGEN_CATEGORIES_ROOT.');
	}
}
