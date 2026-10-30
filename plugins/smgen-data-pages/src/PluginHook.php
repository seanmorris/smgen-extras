<?php

declare(strict_types=1);

namespace SmgenDataPages;

use RuntimeException;

/**
 * Adapts the SMGen prepare-hook context to the shared page generator.
 */
final class PluginHook
{
	/** Read the private build context and generate this plugin's managed pages. */
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
			throw new RuntimeException('smgen-data-pages requires an smgen/v1 prepare context.');
		}
		if(($context['plugin']['id'] ?? null) !== 'smgen-data-pages')
		{
			throw new RuntimeException('Plugin context ID does not match smgen-data-pages.');
		}
		$projectRoot = $context['project']['root'] ?? null;
		$output = $context['paths']['generatedPages'] ?? null;
		$options = $context['plugin']['options'] ?? [];
		if(!is_string($projectRoot) || !is_string($output) || !is_array($options))
		{
			throw new RuntimeException('Plugin context is missing project or generated-page paths.');
		}
		foreach(array_keys($options) as $key)
		{
			if($key !== 'config')
			{
				throw new RuntimeException('Unknown smgen-data-pages option: ' . (string) $key);
			}
		}
		$config = $options['config'] ?? '.smgen-data-pages.yaml';
		if(!is_string($config) || $config === '')
		{
			throw new RuntimeException('smgen-data-pages config option must be a nonempty path.');
		}
		$count = (new DataPages($projectRoot, $config))->generate($output);
		fwrite(STDOUT, 'smgen-data-pages generated ' . $count . " page(s).\n");
	}
}
