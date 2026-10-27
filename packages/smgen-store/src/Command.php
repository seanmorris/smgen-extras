<?php

declare(strict_types=1);

namespace SmgenStore;

use InvalidArgumentException;

/**
 * Implements package-owned store validation for authors and CI jobs.
 */
final class Command
{
	/** Parse command arguments, validate requested paths, and return a status. */
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
			fwrite(STDOUT, "smgen-store 0.1.0\n");
			return 0;
		}
		if($command !== 'validate')
		{
			throw new InvalidArgumentException('Unknown command: ' . $command);
		}

		$root = getcwd();
		$paths = [];
		while($arguments !== [])
		{
			$argument = array_shift($arguments);
			if($argument === '--root')
			{
				$value = array_shift($arguments);
				if(!is_string($value) || $value === '')
				{
					throw new InvalidArgumentException('--root requires a directory.');
				}
				$root = $value;
				continue;
			}
			if(!is_string($argument) || str_starts_with($argument, '-'))
			{
				throw new InvalidArgumentException('Unknown option: ' . (string) $argument);
			}
			$paths[] = $argument;
		}
		if(!is_string($root) || $root === '')
		{
			throw new InvalidArgumentException('Unable to determine the project root.');
		}
		$count = (new StoreValidator($root))->validatePaths($paths === [] ? ['pages'] : $paths);
		fwrite(STDOUT, 'Validated ' . $count . " store page(s).\n");
		return 0;
	}

	/** Return concise help for direct and npm-script use. */
	private static function usage(): string
	{
		return <<<'TEXT'
Usage:
  smgen-store validate [--root DIR] [PATH ...]
  smgen-store --version

PATH defaults to pages. Directories are scanned recursively for Markdown files.
TEXT
 . "\n";
	}
}
