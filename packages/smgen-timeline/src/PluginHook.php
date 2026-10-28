<?php

declare(strict_types=1);

namespace SmgenTimeline;

use RuntimeException;

/**
 * Generates day rollup pages for every calendar day with more than one dated page.
 */
final class PluginHook
{
	/** Read the prepare context and generate the day rollups through smgen-categories. */
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
			throw new RuntimeException('smgen-timeline requires an smgen/v1 prepare context.');
		}
		if(($context['plugin']['id'] ?? null) !== 'smgen-timeline')
		{
			throw new RuntimeException('Plugin context ID does not match smgen-timeline.');
		}
		$projectRoot = $context['project']['root'] ?? null;
		$pages = $context['paths']['pages'] ?? null;
		$output = $context['paths']['generatedPages'] ?? null;
		if(!is_string($projectRoot) || !is_string($pages) || !is_string($output))
		{
			throw new RuntimeException('Plugin context is missing project, pages, or generated-page paths.');
		}
		$count = self::generate($projectRoot, $pages, $output, $context['plugin']['options'] ?? []);
		fwrite(STDOUT, 'smgen-timeline generated ' . $count . " day rollup page(s).\n");
	}

	/**
	 * Generate day rollups with the implicit category definition.
	 *
	 * @param string $projectRoot Site root.
	 * @param string $pagesRoot Pages directory to scan.
	 * @param string $outputRoot Managed directory to install rollups into.
	 * @param mixed $options Plugin options: output, template, pages, exclude.
	 * @return int Number of rollup pages written.
	 */
	public static function generate(string $projectRoot, string $pagesRoot, string $outputRoot, mixed $options): int
	{
		Dependencies::loadCategories($projectRoot);
		$categories = \SmgenCategories\Categories::fromDefinitions($projectRoot, [Timeline::DAY_CATEGORY => self::definition($projectRoot, $options)]);
		return $categories->generate($pagesRoot, $outputRoot, 'smgen-timeline');
	}

	/**
	 * Build the day category definition from plugin options.
	 *
	 * @param string $projectRoot Site root used to resolve a site template.
	 * @param mixed $options Plugin options mapping.
	 * @return array<string, mixed> Category definition for smgen-categories.
	 */
	public static function definition(string $projectRoot, mixed $options): array
	{
		if(!is_array($options) || ($options !== [] && array_keys($options) === range(0, count($options) - 1)))
		{
			throw new RuntimeException('smgen-timeline options must be a mapping.');
		}
		foreach(array_keys($options) as $key)
		{
			if(!in_array($key, ['output', 'template', 'pages', 'exclude'], true))
			{
				throw new RuntimeException('Unknown smgen-timeline option: ' . (string) $key);
			}
		}
		$output = $options['output'] ?? 'archive/{value}.md';
		if(!is_string($output) || !str_contains($output, '{value}'))
		{
			throw new RuntimeException('smgen-timeline output must contain {value}, for example archive/{value}.md.');
		}
		$template = dirname(__DIR__) . '/templates/day.md.php';
		if(isset($options['template']))
		{
			if(!is_string($options['template']) || $options['template'] === '' || str_starts_with($options['template'], '/'))
			{
				throw new RuntimeException('smgen-timeline template must be a project-relative path.');
			}
			$resolved = realpath(rtrim($projectRoot, '/') . '/' . $options['template']);
			$root = realpath($projectRoot);
			if($resolved === false || $root === false || !is_file($resolved) || !str_starts_with($resolved, $root . DIRECTORY_SEPARATOR))
			{
				throw new RuntimeException('smgen-timeline template must be a file inside the project: ' . $options['template']);
			}
			$template = $resolved;
		}
		return [
			'field' => 'date',
			'group' => 'day',
			'minimum' => 2,
			'output' => $output,
			'template' => $template,
			'pages' => $options['pages'] ?? ['**/*.md'],
			'exclude' => $options['exclude'] ?? [],
			'unless' => ['archive' => false],
		];
	}
}
