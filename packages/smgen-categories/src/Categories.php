<?php

declare(strict_types=1);

namespace SmgenCategories;

use DateTimeImmutable;
use ErrorException;
use RuntimeException;
use Throwable;

/**
 * Groups pages by a frontmatter field and renders one rollup page per group.
 */
final class Categories
{
	private const GROUPS = ['value', 'day', 'month', 'year'];
	private const SORTS = ['date-desc', 'date-asc', 'title', 'path'];

	private string $projectRoot;
	private array $definitions;
	private TemplateContext $templateContext;

	/**
	 * Read a project's category configuration.
	 *
	 * @param string $projectRoot Project directory that templates and the configuration live under.
	 * @param string|null $configPath Configuration file relative to the project root, or null to start without categories.
	 */
	public function __construct(string $projectRoot, ?string $configPath = '.smgen-categories.yaml')
	{
		$root = realpath($projectRoot);
		if($root === false || !is_dir($root))
		{
			throw new RuntimeException('Project root does not exist: ' . $projectRoot);
		}
		$this->projectRoot = $root;
		$this->templateContext = new TemplateContext();
		$this->definitions = $configPath === null ? [] : $this->readConfig($this->resolveInputFile($configPath, 'Configuration'));
	}

	/**
	 * Build categories from definitions a calling plugin supplies instead of a configuration file.
	 *
	 * Templates in these definitions may be absolute paths, so a plugin can ship its own default.
	 *
	 * @param string $projectRoot Project directory.
	 * @param array<string, mixed> $definitions Category definitions keyed by name.
	 */
	public static function fromDefinitions(string $projectRoot, array $definitions): self
	{
		$instance = new self($projectRoot, null);
		$normalized = [];
		foreach($definitions as $name => $definition)
		{
			$normalized[$instance->categoryName($name)] = $instance->normalizeDefinition((string) $name, $definition, true);
		}
		$instance->definitions = $normalized;
		return $instance;
	}

	/**
	 * Render every rollup page in memory.
	 *
	 * @param string $pagesRoot Pages directory to scan for members.
	 * @param string|null $skipGenerated Plugin ID whose generated pages are excluded from scanning.
	 * @return array<string, string> Markdown documents keyed by generated-relative output path.
	 */
	public function render(string $pagesRoot, ?string $skipGenerated = 'smgen-categories'): array
	{
		$members = (new PageScanner())->scan($pagesRoot, $skipGenerated);
		$outputs = [];
		$reserved = [];
		foreach($this->definitions as $name => $definition)
		{
			$template = $definition['template'];
			foreach($this->groups($name, $definition, $members) as $group)
			{
				if(count($group['members']) < $definition['minimum'])
				{
					continue;
				}
				$output = $this->groupOutput($definition['output'], $group, $name);
				$this->reserveOutput($reserved, $output, 'category ' . $name . ' ' . $group['value']);
				$category = [
					'name' => $name,
					'field' => $definition['field'],
					'group' => $definition['group'],
					'value' => $group['value'],
					'label' => $group['label'],
					'slug' => $group['slug'],
					'count' => count($group['members']),
				];
				$this->templateContext->withDefaults([
					'title' => $group['label'],
					'category' => $category + ['members' => array_map(static fn(array $member): string => $member['path'], $group['members'])],
				]);
				$outputs[$output] = $this->renderTemplate($template, [
					'category' => $category,
					'members' => $group['members'],
					'context' => $this->templateContext,
				], 'category ' . $name . ' ' . $group['value']);
			}
		}
		ksort($outputs);
		return $outputs;
	}

	/** Render all rollups and install them into one empty directory. */
	public function generate(string $pagesRoot, string $outputRoot, ?string $skipGenerated = 'smgen-categories'): int
	{
		$outputs = $this->render($pagesRoot, $skipGenerated);
		$target = $this->resolveOutputRoot($outputRoot);
		$this->writeOutputs($target, $outputs);
		return count($outputs);
	}

	/** Return the normalized category definitions. */
	public function definitions(): array
	{
		return $this->definitions;
	}

	/** Group members for one category, sorted and labelled. */
	private function groups(string $name, array $definition, array $members): array
	{
		$groups = [];
		foreach($members as $member)
		{
			if(!$this->selects($definition, $member))
			{
				continue;
			}
			$raw = self::fieldValue($member['metadata'], $definition['field']);
			if($raw === null)
			{
				continue;
			}
			foreach($this->groupKeys($name, $definition, $member, $raw) as $key)
			{
				if(!isset($groups[$key['value']]))
				{
					$groups[$key['value']] = ['value' => $key['value'], 'label' => $key['label'], 'slug' => $key['slug'], 'members' => []];
				}
				$groups[$key['value']]['members'][] = $member;
			}
		}
		foreach($groups as &$group)
		{
			$group['members'] = $this->sortMembers($group['members'], $definition['sort']);
		}
		unset($group);
		ksort($groups, SORT_STRING);
		return array_values($groups);
	}

	/** Derive the group value, label, and slug for one member of a category. */
	private function groupKeys(string $name, array $definition, array $member, mixed $raw): array
	{
		if($definition['group'] === 'value')
		{
			$values = is_array($raw) ? $raw : [$raw];
			$keys = [];
			foreach($values as $value)
			{
				if(!is_scalar($value) || $value === '' || $value === false)
				{
					throw new RuntimeException('Category ' . $name . ' field ' . $definition['field'] . ' must be a scalar or a list of scalars in ' . $member['source'] . '.');
				}
				$text = trim((string) $value);
				if($text === '')
				{
					continue;
				}
				$keys[] = ['value' => $text, 'label' => $text, 'slug' => $this->templateContext->pathSegment($text)];
			}
			return $keys;
		}
		if(!is_scalar($raw) || trim((string) $raw) === '')
		{
			throw new RuntimeException('Category ' . $name . ' needs a date in ' . $definition['field'] . ' for ' . $member['source'] . '.');
		}
		try
		{
			$date = new DateTimeImmutable(trim((string) $raw));
		}
		catch(Throwable $error)
		{
			throw new RuntimeException('Invalid date in ' . $member['source'] . ' for category ' . $name . ': ' . trim((string) $raw), 0, $error);
		}
		return [match($definition['group'])
		{
			'day' => ['value' => $date->format('Y-m-d'), 'label' => $date->format('F j, Y'), 'slug' => $date->format('Y-m-d')],
			'month' => ['value' => $date->format('Y-m'), 'label' => $date->format('F Y'), 'slug' => $date->format('Y-m')],
			default => ['value' => $date->format('Y'), 'label' => $date->format('Y'), 'slug' => $date->format('Y')],
		}];
	}

	/**
	 * Read a possibly nested frontmatter value by dotted path.
	 *
	 * @param array<string, mixed> $metadata Page frontmatter.
	 * @param string $field Key or dotted path such as product.category.
	 * @return mixed The value, or null when any segment is missing.
	 */
	public static function fieldValue(array $metadata, string $field): mixed
	{
		$current = $metadata;
		foreach(explode('.', $field) as $segment)
		{
			if(!is_array($current) || !array_key_exists($segment, $current))
			{
				return null;
			}
			$current = $current[$segment];
		}
		return $current;
	}

	/** Report whether a member's public-relative source matches the category's page globs. */
	private function selects(array $definition, array $member): bool
	{
		if(array_key_exists('category', $member['metadata']) && is_array($member['metadata']['category']))
		{
			return false;
		}
		$matched = false;
		foreach($definition['pages'] as $pattern)
		{
			if(preg_match($pattern, $member['relative']) === 1)
			{
				$matched = true;
				break;
			}
		}
		if(!$matched)
		{
			return false;
		}
		foreach($definition['exclude'] as $pattern)
		{
			if(preg_match($pattern, $member['relative']) === 1)
			{
				return false;
			}
		}
		foreach($definition['where'] as $key => $value)
		{
			if(!array_key_exists($key, $member['metadata']) || $member['metadata'][$key] !== $value)
			{
				return false;
			}
		}
		foreach($definition['unless'] as $key => $value)
		{
			if(array_key_exists($key, $member['metadata']) && $member['metadata'][$key] === $value)
			{
				return false;
			}
		}
		return true;
	}

	/** Sort one group's members by the configured order. */
	private function sortMembers(array $members, string $sort): array
	{
		usort($members, static function(array $a, array $b) use ($sort): int {
			switch($sort)
			{
				case 'title':
					return strcasecmp($a['title'], $b['title']) ?: strcmp($a['path'], $b['path']);
				case 'path':
					return strcmp($a['path'], $b['path']);
				default:
					$left = $a['date'] === null ? 0 : (strtotime($a['date']) ?: 0);
					$right = $b['date'] === null ? 0 : (strtotime($b['date']) ?: 0);
					$order = $sort === 'date-asc' ? $left <=> $right : $right <=> $left;
					return $order ?: strcmp($a['path'], $b['path']);
			}
		});
		return $members;
	}

	/** Expand the output pattern for one group and validate the result. */
	private function groupOutput(string $pattern, array $group, string $name): string
	{
		$output = preg_replace_callback('/\{(slug|value)\}/', function(array $match) use ($group, $name): string {
			$value = (string) $group[$match[1]];
			if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,254}$/', $value) || $value === '.' || $value === '..')
			{
				throw new RuntimeException('Category ' . $name . ' value "' . $group['value'] . '" is not a safe output segment; use {slug} instead of {value}.');
			}
			return $value;
		}, $pattern);
		if($output === null || str_contains($output, '{') || str_contains($output, '}'))
		{
			throw new RuntimeException('Category ' . $name . ' output may only use {slug} and {value} placeholders.');
		}
		return $this->validateOutputPath($output, 'category ' . $name);
	}

	/** Read and normalize the YAML configuration. */
	private function readConfig(string $path): array
	{
		set_error_handler(static function(int $severity, string $message) use ($path): never {
			throw new RuntimeException('Invalid YAML in ' . $path . ': ' . $message);
		});
		try
		{
			$value = yaml_parse_file($path);
		}
		finally
		{
			restore_error_handler();
		}
		if(!is_array($value) || self::isList($value))
		{
			throw new RuntimeException('Category configuration must be a YAML mapping: ' . $path);
		}
		$this->rejectUnknown($value, ['version', 'categories'], 'configuration');
		if(($value['version'] ?? null) !== 1)
		{
			throw new RuntimeException('Category configuration requires version: 1.');
		}
		$categories = $value['categories'] ?? null;
		if(!is_array($categories) || $categories === [] || self::isList($categories))
		{
			throw new RuntimeException('Category configuration requires a nonempty categories mapping.');
		}
		$normalized = [];
		foreach($categories as $name => $definition)
		{
			$normalized[$this->categoryName($name)] = $this->normalizeDefinition((string) $name, $definition, false);
		}
		return $normalized;
	}

	/** Validate a category name. */
	private function categoryName(mixed $name): string
	{
		if(!is_string($name) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $name))
		{
			throw new RuntimeException('Category names may contain letters, numbers, periods, underscores, and hyphens.');
		}
		return $name;
	}

	/** Validate one category definition and apply defaults. */
	private function normalizeDefinition(string $name, mixed $definition, bool $trusted): array
	{
		if(!is_array($definition) || self::isList($definition))
		{
			throw new RuntimeException('Category ' . $name . ' must be a mapping.');
		}
		$this->rejectUnknown($definition, ['field', 'pages', 'exclude', 'where', 'unless', 'group', 'output', 'template', 'minimum', 'sort'], 'category ' . $name);
		$field = $definition['field'] ?? null;
		if(!is_string($field) || !preg_match('/^[A-Za-z_][A-Za-z0-9_-]*(?:\.[A-Za-z_][A-Za-z0-9_-]*)*$/', $field))
		{
			throw new RuntimeException('Category ' . $name . ' requires a frontmatter field name such as topic or product.category.');
		}
		$group = $definition['group'] ?? 'value';
		if(!is_string($group) || !in_array($group, self::GROUPS, true))
		{
			throw new RuntimeException('Category ' . $name . ' group must be one of ' . implode(', ', self::GROUPS) . '.');
		}
		$sort = $definition['sort'] ?? 'date-desc';
		if(!is_string($sort) || !in_array($sort, self::SORTS, true))
		{
			throw new RuntimeException('Category ' . $name . ' sort must be one of ' . implode(', ', self::SORTS) . '.');
		}
		$minimum = $definition['minimum'] ?? 1;
		if(!is_int($minimum) || $minimum < 1)
		{
			throw new RuntimeException('Category ' . $name . ' minimum must be a positive integer.');
		}
		$output = $definition['output'] ?? null;
		if(!is_string($output) || $output === '')
		{
			throw new RuntimeException('Category ' . $name . ' requires an output pattern such as topics/{slug}.md.');
		}
		if(!str_contains($output, '{slug}') && !str_contains($output, '{value}'))
		{
			throw new RuntimeException('Category ' . $name . ' output must contain {slug} or {value}.');
		}
		$template = $definition['template'] ?? null;
		if($template === null)
		{
			$template = dirname(__DIR__) . '/templates/category.md.php';
		}
		elseif(!is_string($template) || $template === '')
		{
			throw new RuntimeException('Category ' . $name . ' template must be a file path.');
		}
		else
		{
			$template = $this->resolveInputFile($template, 'Template for category ' . $name, $trusted);
		}
		foreach(['where', 'unless'] as $filter)
		{
			$mapping = $definition[$filter] ?? [];
			if(!is_array($mapping) || ($mapping !== [] && self::isList($mapping)))
			{
				throw new RuntimeException('Category ' . $name . ' ' . $filter . ' must be a mapping of frontmatter keys to values.');
			}
		}
		return [
			'field' => $field,
			'pages' => array_map([$this, 'globRegex'], $this->stringList($definition['pages'] ?? ['**/*.md'], 'category ' . $name . ' pages')),
			'exclude' => array_map([$this, 'globRegex'], $this->stringList($definition['exclude'] ?? [], 'category ' . $name . ' exclude')),
			'where' => $definition['where'] ?? [],
			'unless' => $definition['unless'] ?? [],
			'group' => $group,
			'output' => $output,
			'template' => $template,
			'minimum' => $minimum,
			'sort' => $sort,
		];
	}

	/** Convert a source-relative glob into an anchored regular expression. */
	private function globRegex(string $glob): string
	{
		$pattern = '';
		$length = strlen($glob);
		for($index = 0; $index < $length; $index++)
		{
			$character = $glob[$index];
			if($character === '*')
			{
				if(($glob[$index + 1] ?? '') === '*')
				{
					$pattern .= '.*';
					$index++;
					if(($glob[$index + 1] ?? '') === '/')
					{
						$index++;
						$pattern .= '(?:/)?';
					}
					continue;
				}
				$pattern .= '[^/]*';
				continue;
			}
			if($character === '?')
			{
				$pattern .= '[^/]';
				continue;
			}
			$pattern .= preg_quote($character, '~');
		}
		return '~^' . $pattern . '$~';
	}

	/** Execute a trusted template with documented variables and captured output. */
	private function renderTemplate(string $template, array $variables, string $label): string
	{
		$level = ob_get_level();
		ob_start();
		set_error_handler(static function(int $severity, string $message, string $file, int $line): never {
			throw new ErrorException($message, 0, $severity, $file, $line);
		});
		try
		{
			(static function(string $__template, array $__variables): void {
				extract($__variables, EXTR_SKIP);
				include $__template;
			})($template, $variables);
			$output = (string) ob_get_contents();
		}
		catch(Throwable $error)
		{
			while(ob_get_level() > $level)
			{
				ob_end_clean();
			}
			throw new RuntimeException('Template failed for ' . $label . ': ' . $error->getMessage(), 0, $error);
		}
		finally
		{
			restore_error_handler();
		}
		ob_end_clean();
		if($output === '' || !mb_check_encoding($output, 'UTF-8'))
		{
			throw new RuntimeException('Template output must be nonempty UTF-8 for ' . $label . '.');
		}
		return $output;
	}

	/** Normalize a relative Markdown path and reject traversal or ambiguous forms. */
	private function validateOutputPath(string $path, string $label): string
	{
		if($path === '' || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/') || !str_ends_with(strtolower($path), '.md'))
		{
			throw new RuntimeException('Invalid Markdown output path for ' . $label . ': ' . $path);
		}
		foreach(explode('/', $path) as $part)
		{
			if($part === '' || $part === '.' || $part === '..')
			{
				throw new RuntimeException('Output paths cannot be empty or traverse directories: ' . $path);
			}
		}
		return $path;
	}

	/** Reserve one case-insensitive output path and report collisions. */
	private function reserveOutput(array &$outputs, string $path, string $owner): void
	{
		$key = mb_strtolower($path, 'UTF-8');
		if(array_key_exists($key, $outputs))
		{
			throw new RuntimeException('Generated output collision at ' . $path . ' between ' . $outputs[$key] . ' and ' . $owner . '.');
		}
		$outputs[$key] = $owner;
	}

	/** Resolve an existing regular file, keeping untrusted paths inside the project. */
	private function resolveInputFile(string $path, string $label, bool $allowOutside = false): string
	{
		if($path === '' || str_contains($path, "\0") || str_contains($path, '\\'))
		{
			throw new RuntimeException($label . ' path is invalid.');
		}
		$candidate = str_starts_with($path, '/') ? $path : $this->projectRoot . '/' . $path;
		$resolved = realpath($candidate);
		if($resolved === false || !is_file($resolved))
		{
			throw new RuntimeException($label . ' must be a regular file: ' . $path);
		}
		if(!$allowOutside)
		{
			$this->rejectSymlinkSegments($candidate, $label);
			if(!$this->isWithinProject($resolved))
			{
				throw new RuntimeException($label . ' must stay inside the project: ' . $path);
			}
		}
		return $resolved;
	}

	/** Resolve a generated-page root that remains within the project directory. */
	private function resolveOutputRoot(string $path): string
	{
		if($path === '' || str_contains($path, "\0") || str_contains($path, '\\'))
		{
			throw new RuntimeException('Generated output root is invalid.');
		}
		$candidate = rtrim(str_starts_with($path, '/') ? $path : $this->projectRoot . '/' . $path, '/');
		if($candidate === $this->projectRoot || !$this->isWithinProject($candidate))
		{
			throw new RuntimeException('Generated output root must stay below the project root: ' . $path);
		}
		$this->rejectSymlinkSegments($candidate, 'Generated output root');
		return $candidate;
	}

	/** Report whether one absolute path is inside the canonical project root. */
	private function isWithinProject(string $path): bool
	{
		return $path === $this->projectRoot || str_starts_with($path, $this->projectRoot . DIRECTORY_SEPARATOR);
	}

	/** Reject existing symbolic links between the project root and a target. */
	private function rejectSymlinkSegments(string $path, string $label): void
	{
		if(!$this->isWithinProject($path))
		{
			throw new RuntimeException($label . ' escapes the project root: ' . $path);
		}
		$relative = ltrim(substr($path, strlen($this->projectRoot)), DIRECTORY_SEPARATOR);
		$current = $this->projectRoot;
		foreach($relative === '' ? [] : explode(DIRECTORY_SEPARATOR, $relative) as $part)
		{
			if($part === '' || $part === '.' || $part === '..')
			{
				throw new RuntimeException($label . ' contains an invalid path segment.');
			}
			$current .= DIRECTORY_SEPARATOR . $part;
			if(is_link($current))
			{
				throw new RuntimeException($label . ' cannot pass through a symbolic link: ' . $current);
			}
		}
	}

	/** Install rendered files through a temporary sibling directory. */
	private function writeOutputs(string $target, array $outputs): void
	{
		$parent = dirname($target);
		if(!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent))
		{
			throw new RuntimeException('Unable to create generated output parent: ' . $parent);
		}
		$this->rejectSymlinkSegments($parent, 'Generated output parent');
		if(file_exists($target) || is_link($target))
		{
			if(is_link($target) || !is_dir($target))
			{
				throw new RuntimeException('Generated output root must be an empty directory: ' . $target);
			}
			if(array_values(array_diff(scandir($target) ?: [], ['.', '..'])) !== [])
			{
				throw new RuntimeException('Generated output root is not empty: ' . $target);
			}
		}
		$temp = $parent . '/.' . basename($target) . '.tmp.' . bin2hex(random_bytes(6));
		if(!mkdir($temp, 0755))
		{
			throw new RuntimeException('Unable to create temporary generated output: ' . $temp);
		}
		try
		{
			foreach($outputs as $relative => $content)
			{
				$destination = $temp . '/' . $relative;
				$directory = dirname($destination);
				if(!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory))
				{
					throw new RuntimeException('Unable to create generated directory: ' . $directory);
				}
				if(file_put_contents($destination, $content, LOCK_EX) === false)
				{
					throw new RuntimeException('Unable to write generated page: ' . $destination);
				}
			}
			if(is_dir($target) && !rmdir($target))
			{
				throw new RuntimeException('Unable to replace empty generated output: ' . $target);
			}
			if(!rename($temp, $target))
			{
				throw new RuntimeException('Unable to install generated output: ' . $target);
			}
			$temp = '';
		}
		finally
		{
			if($temp !== '' && is_dir($temp))
			{
				$this->removeTree($temp);
			}
		}
	}

	/** Remove a temporary directory without following symbolic links. */
	private function removeTree(string $path): void
	{
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST,
		);
		foreach($iterator as $entry)
		{
			$entryPath = $entry->getPathname();
			if($entry->isLink() || $entry->isFile())
			{
				unlink($entryPath);
			}
			else
			{
				rmdir($entryPath);
			}
		}
		rmdir($path);
	}

	/** Reject keys outside a versioned configuration mapping. */
	private function rejectUnknown(array $mapping, array $allowed, string $label): void
	{
		foreach(array_keys($mapping) as $key)
		{
			if(!in_array($key, $allowed, true))
			{
				throw new RuntimeException('Unknown ' . $label . ' field: ' . (string) $key);
			}
		}
	}

	/** Normalize a list of nonempty strings. */
	private function stringList(mixed $value, string $label): array
	{
		if(is_string($value))
		{
			$value = [$value];
		}
		if(!is_array($value) || ($value !== [] && !self::isList($value)))
		{
			throw new RuntimeException(ucfirst($label) . ' must be a list of strings.');
		}
		foreach($value as $item)
		{
			if(!is_string($item) || $item === '')
			{
				throw new RuntimeException(ucfirst($label) . ' must be a list of nonempty strings.');
			}
		}
		return array_values($value);
	}

	/** Report whether an array uses sequential integer keys. */
	private static function isList(array $value): bool
	{
		return $value === [] || array_keys($value) === range(0, count($value) - 1);
	}
}
