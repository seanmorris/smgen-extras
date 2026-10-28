<?php

declare(strict_types=1);

namespace SmgenCategories;

use InvalidArgumentException;

/**
 * Implements the package-owned local validation and generation commands.
 */
final class Command
{
	/** Parse CLI arguments, execute one command, and return its process status. */
	public static function run(array $arguments): int
	{
		array_shift($arguments);
		$command = array_shift($arguments) ?? 'help';
		if(in_array($command, ['help', '--help', '-h'], true))
		{
			fwrite(STDOUT, self::usage());
			return 0;
		}
		if(in_array($command, ['version', '--version', '-V'], true))
		{
			fwrite(STDOUT, "smgen-categories 0.1.0\n");
			return 0;
		}
		if(!in_array($command, ['validate', 'generate'], true))
		{
			throw new InvalidArgumentException('Unknown command: ' . $command);
		}

		$options = self::options($arguments);
		$root = $options['root'] ?? getcwd();
		if(!is_string($root) || $root === '')
		{
			throw new InvalidArgumentException('Unable to determine the project root.');
		}
		$config = $options['config'] ?? '.smgen-categories.yaml';
		$pages = $options['pages'] ?? rtrim($root, '/') . '/pages';
		$categories = new Categories($root, $config);
		if($command === 'validate')
		{
			$outputs = $categories->render($pages);
			fwrite(STDOUT, 'Validated ' . count($outputs) . " rollup page(s).\n");
			return 0;
		}
		$output = $options['output'] ?? null;
		if(!is_string($output) || $output === '')
		{
			throw new InvalidArgumentException('generate requires --output DIRECTORY.');
		}
		$count = $categories->generate($pages, $output);
		fwrite(STDOUT, 'Generated ' . $count . ' rollup page(s) in ' . $output . ".\n");
		return 0;
	}

	/** Parse value-bearing command options and reject positional arguments. */
	private static function options(array $arguments): array
	{
		$options = [];
		$allowed = ['--root' => 'root', '--config' => 'config', '--pages' => 'pages', '--output' => 'output'];
		while($arguments !== [])
		{
			$option = array_shift($arguments);
			if(!is_string($option) || !isset($allowed[$option]))
			{
				throw new InvalidArgumentException('Unknown option: ' . (string) $option);
			}
			$value = array_shift($arguments);
			if(!is_string($value) || $value === '')
			{
				throw new InvalidArgumentException($option . ' requires a value.');
			}
			$options[$allowed[$option]] = $value;
		}
		return $options;
	}

	/** Return concise help for commands intended for authors and CI. */
	private static function usage(): string
	{
		return <<<'TEXT'
Usage:
  smgen-categories validate [--root DIR] [--config FILE] [--pages DIR]
  smgen-categories generate --output DIR [--root DIR] [--config FILE] [--pages DIR]
  smgen-categories --version

validate renders every rollup page in memory without changing the project.
generate installs the validated Markdown files into an empty output directory.
TEXT
 . "\n";
	}
}
