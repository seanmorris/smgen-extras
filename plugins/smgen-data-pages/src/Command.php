<?php

declare(strict_types=1);

namespace SmgenDataPages;

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
			fwrite(STDOUT, "smgen-data-pages 0.1.0\n");
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
		$config = $options['config'] ?? '.smgen-data-pages.yaml';
		$pages = new DataPages($root, $config);
		if($command === 'validate')
		{
			$outputs = $pages->render();
			fwrite(STDOUT, 'Validated ' . count($outputs) . " generated page(s).\n");
			return 0;
		}
		$output = $options['output'] ?? null;
		if(!is_string($output) || $output === '')
		{
			throw new InvalidArgumentException('generate requires --output DIRECTORY.');
		}
		$count = $pages->generate($output);
		fwrite(STDOUT, 'Generated ' . $count . ' page(s) in ' . $output . ".\n");
		return 0;
	}

	/** Parse value-bearing command options and reject positional arguments. */
	private static function options(array $arguments): array
	{
		$options = [];
		$allowed = ['--root' => 'root', '--config' => 'config', '--output' => 'output'];
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
  smgen-data-pages validate [--root DIR] [--config FILE]
  smgen-data-pages generate --output DIR [--root DIR] [--config FILE]
  smgen-data-pages --version

validate renders every template in memory without changing the project.
generate installs the validated Markdown files into an empty output directory.
TEXT
 . "\n";
	}
}
