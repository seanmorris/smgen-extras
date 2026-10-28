<?php

declare(strict_types=1);

namespace SmgenTimeline;

use RuntimeException;

/**
 * Generates day rollup pages during prepare and calendar month partials during finalize.
 */
final class PluginHook
{
	/** Read the hook context and run the prepare or finalize phase. */
	public static function run(): void
	{
		$context = self::context();
		if($context['phase'] === 'finalize')
		{
			$count = self::writePartials($context);
			fwrite(STDOUT, 'smgen-timeline wrote ' . $count . " calendar partial(s).\n");
			return;
		}
		$projectRoot = $context['project']['root'] ?? null;
		$pages = $context['paths']['pages'] ?? null;
		$output = $context['paths']['generatedPages'] ?? null;
		if(!is_string($projectRoot) || !is_string($pages) || !is_string($output))
		{
			throw new RuntimeException('Plugin context is missing project, pages, or generated-page paths.');
		}
		$baseUrl = $context['site']['baseUrl'] ?? '';
		$count = self::generate($projectRoot, $pages, $output, $context['plugin']['options'] ?? [], is_string($baseUrl) ? $baseUrl : '');
		fwrite(STDOUT, 'smgen-timeline generated ' . $count . " day rollup page(s).\n");
	}

	/**
	 * Read and validate the SMGEN_PLUGIN_CONTEXT file.
	 *
	 * @return array<string, mixed> Decoded prepare or finalize context.
	 */
	private static function context(): array
	{
		$contextPath = getenv('SMGEN_PLUGIN_CONTEXT');
		if(!is_string($contextPath) || $contextPath === '' || !is_file($contextPath))
		{
			throw new RuntimeException('SMGEN_PLUGIN_CONTEXT must name a readable build context.');
		}
		$context = json_decode((string) file_get_contents($contextPath), true, 512, JSON_THROW_ON_ERROR);
		if(!is_array($context) || ($context['apiVersion'] ?? null) !== 'smgen/v1' || !in_array($context['phase'] ?? null, ['prepare', 'finalize'], true))
		{
			throw new RuntimeException('smgen-timeline requires an smgen/v1 prepare or finalize context.');
		}
		if(($context['plugin']['id'] ?? null) !== 'smgen-timeline')
		{
			throw new RuntimeException('Plugin context ID does not match smgen-timeline.');
		}
		return $context;
	}

	/**
	 * Write one calendar partial per month for every timeline menu into the staged output.
	 *
	 * @param array<string, mixed> $context Finalize context with the stage path and base URL.
	 * @return int Number of partials written.
	 */
	public static function writePartials(array $context, ?string $navigationPath = null): int
	{
		$stage = $context['paths']['stage'] ?? null;
		if(!is_string($stage) || !is_dir($stage))
		{
			throw new RuntimeException('Finalize context is missing the staged output directory.');
		}
		$navigationPath ??= getenv('SMGEN_NAV_DATA');
		if(!is_string($navigationPath) || !is_file($navigationPath))
		{
			throw new RuntimeException('SMGEN_NAV_DATA must name the compiled navigation data.');
		}
		$navigation = json_decode((string) file_get_contents($navigationPath), true, 512, JSON_THROW_ON_ERROR);
		$baseUrl = $context['site']['baseUrl'] ?? '';
		Timeline::setBaseUrl(is_string($baseUrl) ? $baseUrl : '');
		$count = 0;
		foreach($navigation['menus'] ?? [] as $name => $menu)
		{
			if(!is_array($menu) || ($menu['renderer'] ?? null) !== 'timeline')
			{
				continue;
			}
			$menu['name'] = (string) ($menu['name'] ?? $name);
			if(preg_match('/^[A-Za-z0-9_-]+$/', $menu['name']) !== 1)
			{
				throw new RuntimeException('Timeline menu names must be simple tokens: ' . $menu['name']);
			}
			foreach(Timeline::partials($menu) as $publicPath => $html)
			{
				$file = rtrim($stage, '/') . $publicPath;
				if(!is_dir(dirname($file)) && !mkdir(dirname($file), 0755, true) && !is_dir(dirname($file)))
				{
					throw new RuntimeException('Unable to create the calendar partial directory: ' . dirname($file));
				}
				if(file_put_contents($file, $html) === false)
				{
					throw new RuntimeException('Unable to write the calendar partial: ' . $file);
				}
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Generate day rollups with the implicit category definition.
	 *
	 * @param string $projectRoot Site root.
	 * @param string $pagesRoot Pages directory to scan.
	 * @param string $outputRoot Managed directory to install rollups into.
	 * @param mixed $options Plugin options: output, template, pages, exclude.
	 * @param string $baseUrl Site base URL for member links.
	 * @return int Number of rollup pages written.
	 */
	public static function generate(string $projectRoot, string $pagesRoot, string $outputRoot, mixed $options, string $baseUrl = ''): int
	{
		Dependencies::loadCategories($projectRoot);
		$categories = \SmgenCategories\Categories::fromDefinitions($projectRoot, [Timeline::DAY_CATEGORY => self::definition($projectRoot, $options)], $baseUrl);
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
