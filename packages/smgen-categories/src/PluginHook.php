<?php

declare(strict_types=1);

namespace SmgenCategories;

use RuntimeException;

/**
 * Adapts the SMGen prepare-hook context to the rollup generator.
 */
final class PluginHook
{
	/** Read the private build context and generate this plugin's managed pages. */
	public static function run(): void
	{
		$context = self::context('smgen-categories');
		$options = $context['plugin']['options'] ?? [];
		if(!is_array($options))
		{
			throw new RuntimeException('smgen-categories options must be a mapping.');
		}
		foreach(array_keys($options) as $key)
		{
			if($key !== 'config')
			{
				throw new RuntimeException('Unknown smgen-categories option: ' . (string) $key);
			}
		}
		$config = $options['config'] ?? '.smgen-categories.yaml';
		if(!is_string($config) || $config === '')
		{
			throw new RuntimeException('smgen-categories config option must be a nonempty path.');
		}
		$categories = new Categories($context['project']['root'], $config);
		$count = $categories->generate($context['paths']['pages'], $context['paths']['generatedPages']);
		fwrite(STDOUT, 'smgen-categories generated ' . $count . " rollup page(s).\n");
	}

	/**
	 * Read and check the smgen/v1 prepare context for a plugin.
	 *
	 * @param string $pluginId Plugin ID the context must belong to.
	 * @return array<string, mixed> Decoded context with project root, pages, and generated-page paths.
	 */
	public static function context(string $pluginId): array
	{
		$contextPath = getenv('SMGEN_PLUGIN_CONTEXT');
		if(!is_string($contextPath) || $contextPath === '' || !is_file($contextPath))
		{
			throw new RuntimeException('SMGEN_PLUGIN_CONTEXT must name a readable build context.');
		}
		$context = json_decode((string) file_get_contents($contextPath), true, 512, JSON_THROW_ON_ERROR);
		if(!is_array($context) || ($context['apiVersion'] ?? null) !== 'smgen/v1' || ($context['phase'] ?? null) !== 'prepare')
		{
			throw new RuntimeException($pluginId . ' requires an smgen/v1 prepare context.');
		}
		if(($context['plugin']['id'] ?? null) !== $pluginId)
		{
			throw new RuntimeException('Plugin context ID does not match ' . $pluginId . '.');
		}
		foreach([['project', 'root'], ['paths', 'pages'], ['paths', 'generatedPages']] as [$section, $key])
		{
			if(!is_string($context[$section][$key] ?? null) || $context[$section][$key] === '')
			{
				throw new RuntimeException('Plugin context is missing ' . $section . '.' . $key . '.');
			}
		}
		return $context;
	}
}
