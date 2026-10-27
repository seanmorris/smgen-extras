<?php

declare(strict_types=1);

namespace SmgenStore;

use RuntimeException;

/**
 * Validates authored and previously generated pages during SMGen preparation.
 */
final class PluginHook
{
	/** Read an smgen/v1 context and validate the complete page source tree. */
	public static function run(): void
	{
		$contextPath = getenv('SMGEN_PLUGIN_CONTEXT');
		if(!is_string($contextPath) || $contextPath === '' || !is_file($contextPath))
		{
			throw new RuntimeException('SMGEN_PLUGIN_CONTEXT must name a readable build context.');
		}
		$context = json_decode((string) file_get_contents($contextPath), true, 512, JSON_THROW_ON_ERROR);
		if(!is_array($context) || ($context['apiVersion'] ?? null) !== 'smgen/v1' || ($context['phase'] ?? null) !== 'prepare')
		{
			throw new RuntimeException('smgen-store requires an smgen/v1 prepare context.');
		}
		if(($context['plugin']['id'] ?? null) !== 'smgen-store')
		{
			throw new RuntimeException('Plugin context ID does not match smgen-store.');
		}
		$options = $context['plugin']['options'] ?? [];
		if($options !== [])
		{
			throw new RuntimeException('smgen-store does not accept plugin options in schema 1.');
		}
		$projectRoot = $context['project']['root'] ?? null;
		$pages = $context['paths']['pages'] ?? null;
		if(!is_string($projectRoot) || !is_string($pages))
		{
			throw new RuntimeException('Plugin context is missing project or page paths.');
		}
		$count = (new StoreValidator($projectRoot))->validatePaths([$pages]);
		fwrite(STDOUT, 'smgen-store validated ' . $count . " store page(s).\n");
	}
}
