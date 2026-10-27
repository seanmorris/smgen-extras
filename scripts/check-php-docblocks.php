<?php

declare(strict_types=1);

/**
 * Checks PHP declarations for descriptive docblocks without loading package code.
 */
final class PhpDocChecker
{
	/** Token kinds that may appear between a docblock and its declaration. */
	private const MODIFIERS = [
		T_ABSTRACT,
		T_FINAL,
		T_PRIVATE,
		T_PROTECTED,
		T_PUBLIC,
		T_STATIC,
	];

	/**
	 * Scan the requested paths and report every declaration without prose.
	 *
	 * @param list<string> $paths Files or directories to inspect.
	 */
	public static function run(array $paths): void
	{
		$files = self::files($paths);
		$failures = [];
		foreach($files as $file)
		{
			self::checkFile($file, $failures);
		}

		if($failures !== [])
		{
			fwrite(STDERR, "Missing descriptive PHPDoc:\n- " . implode("\n- ", $failures) . "\n");
			exit(1);
		}
	}

	/**
	 * Expand input paths into a sorted list of PHP source files.
	 *
	 * @param list<string> $paths Files or directories supplied on the command line.
	 * @return list<string>
	 */
	private static function files(array $paths): array
	{
		$files = [];
		foreach($paths as $path)
		{
			if(is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php')
			{
				$files[] = $path;
				continue;
			}
			if(!is_dir($path))
			{
				throw new RuntimeException('PHPDoc path does not exist: ' . $path);
			}
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
			foreach($iterator as $file)
			{
				if($file->isFile() && $file->getExtension() === 'php')
				{
					$files[] = $file->getPathname();
				}
			}
		}
		sort($files);
		return array_values(array_unique($files));
	}

	/**
	 * Inspect named classes, interfaces, traits, enums, functions, and methods.
	 *
	 * @param list<string> $failures Accumulated diagnostics.
	 */
	private static function checkFile(string $file, array &$failures): void
	{
		$source = file_get_contents($file);
		if($source === false)
		{
			throw new RuntimeException('Unable to read PHP source: ' . $file);
		}
		$tokens = token_get_all($source);
		$declarations = [T_CLASS, T_INTERFACE, T_TRAIT, T_FUNCTION];
		if(defined('T_ENUM'))
		{
			$declarations[] = constant('T_ENUM');
		}
		foreach($tokens as $index => $token)
		{
			if(!is_array($token) || !in_array($token[0], $declarations, true))
			{
				continue;
			}
			$name = self::declarationName($tokens, $index);
			if($name === null)
			{
				continue;
			}
			$comment = self::precedingDocblock($tokens, $index);
			if(!self::hasDescription($comment))
			{
				$failures[] = $file . ':' . $token[2] . ' ' . $name;
			}
		}
	}

	/**
	 * Read the declared name or return null for an anonymous function or class.
	 *
	 * @param array<int, array{int,string,int}|string> $tokens Parsed PHP tokens.
	 */
	private static function declarationName(array $tokens, int $index): ?string
	{
		for($cursor = $index + 1, $count = count($tokens); $cursor < $count; $cursor++)
		{
			$token = $tokens[$cursor];
			if(is_array($token) && in_array($token[0], [T_WHITESPACE, T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG, T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG], true))
			{
				continue;
			}
			if(is_array($token) && $token[0] === T_STRING)
			{
				return $token[1];
			}
			return null;
		}
		return null;
	}

	/**
	 * Find the docblock before a declaration and its visibility modifiers.
	 *
	 * @param array<int, array{int,string,int}|string> $tokens Parsed PHP tokens.
	 */
	private static function precedingDocblock(array $tokens, int $index): ?string
	{
		for($cursor = $index - 1; $cursor >= 0; $cursor--)
		{
			$token = $tokens[$cursor];
			if(is_array($token) && ($token[0] === T_WHITESPACE || in_array($token[0], self::MODIFIERS, true)))
			{
				continue;
			}
			return is_array($token) && $token[0] === T_DOC_COMMENT ? $token[1] : null;
		}
		return null;
	}

	/** Require at least three words of prose before PHPDoc tags. */
	private static function hasDescription(?string $comment): bool
	{
		if($comment === null)
		{
			return false;
		}
		$description = [];
		foreach(preg_split('/\R/', $comment) ?: [] as $line)
		{
			$line = trim(preg_replace('/^\s*\/\*\*?|\*\/\s*$|^\s*\*\s?/', '', $line) ?? '');
			if(str_starts_with($line, '@'))
			{
				break;
			}
			if($line !== '')
			{
				$description[] = $line;
			}
		}
		return count(preg_split('/\s+/', implode(' ', $description), -1, PREG_SPLIT_NO_EMPTY) ?: []) >= 3;
	}
}

$paths = array_slice($argv, 1);
PhpDocChecker::run($paths === [] ? [dirname(__DIR__) . '/packages'] : $paths);
