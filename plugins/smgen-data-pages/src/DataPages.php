<?php

declare(strict_types=1);

namespace SmgenDataPages;

use ErrorException;
use RuntimeException;
use Throwable;

/**
 * Validates tabular datasets and renders their Markdown page templates.
 */
final class DataPages
{
	private readonly string $projectRoot;
	private readonly string $configPath;
	private readonly array $config;
	private readonly TemplateContext $templateContext;

	/** Load and validate one project-relative data-page configuration. */
	public function __construct(string $projectRoot, string $configPath = '.smgen-data-pages.yaml')
	{
		$root = realpath($projectRoot);
		if($root === false || !is_dir($root))
		{
			throw new RuntimeException('Project root does not exist: ' . $projectRoot);
		}
		$this->projectRoot = rtrim($root, DIRECTORY_SEPARATOR);
		$this->configPath = $this->resolveInputFile($configPath, 'Configuration', true);
		$this->config = $this->readConfig($this->configPath);
		$this->templateContext = new TemplateContext();
	}

	/** Render and validate every configured row and collection in memory. */
	public function render(): array
	{
		$outputs = [];
		$reservedOutputs = [];
		foreach($this->config['datasets'] as $datasetName => $dataset)
		{
			$records = $this->readDataset($datasetName, $dataset);
			$template = $this->resolveInputFile($dataset['template'], 'Template for ' . $datasetName);
			foreach($records as $record)
			{
				$output = $this->rowOutput($dataset['output'], $record['row'], $datasetName, $record['rowNumber']);
				$this->reserveOutput($reservedOutputs, $output, $datasetName . ' row ' . $record['rowNumber']);
				$variables = array_merge($record['row'], [
					'row' => $record['row'],
					'sourceRow' => $record['sourceRow'],
					'dataset' => $datasetName,
					'rowNumber' => $record['rowNumber'],
					'context' => $this->templateContext,
				]);
				$outputs[$output] = $this->renderTemplate($template, $variables, $datasetName . ' row ' . $record['rowNumber']);
			}

			foreach($dataset['collections'] as $collectionIndex => $collection)
			{
				$output = $this->validateOutputPath($collection['output'], $datasetName . ' collection ' . ($collectionIndex + 1));
				$this->reserveOutput($reservedOutputs, $output, $datasetName . ' collection ' . ($collectionIndex + 1));
				$collectionTemplate = $this->resolveInputFile($collection['template'], 'Collection template for ' . $datasetName);
				$outputs[$output] = $this->renderTemplate($collectionTemplate, [
					'records' => $records,
					'dataset' => $datasetName,
					'context' => $this->templateContext,
				], $datasetName . ' collection ' . ($collectionIndex + 1));
			}
		}
		ksort($outputs);
		return $outputs;
	}

	/** Render all outputs and install them into one empty project directory. */
	public function generate(string $outputRoot): int
	{
		$outputs = $this->render();
		$target = $this->resolveOutputRoot($outputRoot);
		$this->writeOutputs($target, $outputs);
		return count($outputs);
	}

	/** Parse the versioned YAML configuration and normalize every dataset. */
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
			throw new RuntimeException('Data-page configuration must be a YAML mapping: ' . $path);
		}
		$this->rejectUnknown($value, ['version', 'datasets'], 'configuration');
		if(($value['version'] ?? null) !== 1)
		{
			throw new RuntimeException('Data-page configuration requires version: 1.');
		}
		$datasets = $value['datasets'] ?? null;
		if(!is_array($datasets) || $datasets === [] || self::isList($datasets))
		{
			throw new RuntimeException('Data-page configuration requires a nonempty datasets mapping.');
		}
		$normalized = [];
		foreach($datasets as $name => $dataset)
		{
			if(!is_string($name) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $name))
			{
				throw new RuntimeException('Dataset names may contain letters, numbers, periods, underscores, and hyphens.');
			}
			$normalized[$name] = $this->normalizeDataset($name, $dataset);
		}
		return ['version' => 1, 'datasets' => $normalized];
	}

	/** Validate one dataset definition and supply optional defaults. */
	private function normalizeDataset(string $name, mixed $dataset): array
	{
		if(!is_array($dataset) || self::isList($dataset))
		{
			throw new RuntimeException('Dataset ' . $name . ' must be a mapping.');
		}
		$this->rejectUnknown($dataset, ['source', 'template', 'output', 'key', 'columns', 'required', 'delimiter', 'where', 'collections'], 'dataset ' . $name);
		foreach(['source', 'template', 'output', 'key'] as $field)
		{
			if(!isset($dataset[$field]) || !is_string($dataset[$field]) || trim($dataset[$field]) === '')
			{
				throw new RuntimeException('Dataset ' . $name . ' requires a nonempty ' . $field . ' string.');
			}
		}
		$columns = $dataset['columns'] ?? null;
		if(!is_array($columns) || $columns === [] || self::isList($columns))
		{
			throw new RuntimeException('Dataset ' . $name . ' requires a nonempty columns mapping.');
		}
		foreach($columns as $canonical => $source)
		{
			if(!is_string($canonical) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $canonical) || !is_string($source) || $source === '')
			{
				throw new RuntimeException('Dataset ' . $name . ' columns must map PHP-safe names to nonempty source headers.');
			}
		}
		if(!array_key_exists($dataset['key'], $columns))
		{
			throw new RuntimeException('Dataset ' . $name . ' key must name a canonical column.');
		}
		$required = $this->stringList($dataset['required'] ?? [], 'required columns for ' . $name);
		foreach($required as $column)
		{
			if(!array_key_exists($column, $columns))
			{
				throw new RuntimeException('Dataset ' . $name . ' requires unknown canonical column: ' . $column);
			}
		}
		$where = $dataset['where'] ?? [];
		if(!is_array($where) || ($where !== [] && self::isList($where)))
		{
			throw new RuntimeException('Dataset ' . $name . ' where must be a mapping.');
		}
		foreach($where as $column => $expected)
		{
			if(!is_string($column) || !array_key_exists($column, $columns) || !is_string($expected))
			{
				throw new RuntimeException('Dataset ' . $name . ' where values must be quoted strings keyed by canonical columns.');
			}
		}
		$delimiter = $dataset['delimiter'] ?? (str_ends_with(strtolower($dataset['source']), '.tsv') ? "\t" : ',');
		if(!is_string($delimiter) || strlen($delimiter) !== 1)
		{
			throw new RuntimeException('Dataset ' . $name . ' delimiter must be exactly one byte.');
		}
		$collections = $dataset['collections'] ?? [];
		if(!is_array($collections) || ($collections !== [] && !self::isList($collections)))
		{
			throw new RuntimeException('Dataset ' . $name . ' collections must be a list.');
		}
		$normalizedCollections = [];
		foreach($collections as $index => $collection)
		{
			if(!is_array($collection) || self::isList($collection))
			{
				throw new RuntimeException('Collection ' . ($index + 1) . ' for ' . $name . ' must be a mapping.');
			}
			$this->rejectUnknown($collection, ['template', 'output'], 'collection ' . ($index + 1) . ' for ' . $name);
			foreach(['template', 'output'] as $field)
			{
				if(!isset($collection[$field]) || !is_string($collection[$field]) || trim($collection[$field]) === '')
				{
					throw new RuntimeException('Collection ' . ($index + 1) . ' for ' . $name . ' requires ' . $field . '.');
				}
			}
			if(str_contains($collection['output'], '{') || str_contains($collection['output'], '}'))
			{
				throw new RuntimeException('Collection outputs cannot contain row placeholders: ' . $collection['output']);
			}
			$normalizedCollections[] = $collection;
		}
		return array_replace($dataset, [
			'columns' => $columns,
			'required' => $required,
			'where' => $where,
			'delimiter' => $delimiter,
			'collections' => $normalizedCollections,
		]);
	}

	/** Read one CSV or TSV source while retaining original and mapped values. */
	private function readDataset(string $name, array $dataset): array
	{
		$path = $this->resolveInputFile($dataset['source'], 'Source for ' . $name);
		$raw = file_get_contents($path);
		if($raw === false)
		{
			throw new RuntimeException('Unable to read dataset source: ' . $path);
		}
		if(!mb_check_encoding($raw, 'UTF-8'))
		{
			throw new RuntimeException('Dataset ' . $name . ' is not valid UTF-8.');
		}
		$handle = fopen($path, 'rb');
		if($handle === false)
		{
			throw new RuntimeException('Unable to open dataset source: ' . $path);
		}
		try
		{
			$line = 1;
			$headers = $this->readCsvRecord($handle, $raw, $line, $dataset['delimiter']);
			if($headers === null || $headers['values'] === [null])
			{
				throw new RuntimeException('Dataset ' . $name . ' has no header row.');
			}
			$headerValues = array_map(static fn(mixed $value): string => (string) $value, $headers['values']);
			if(isset($headerValues[0]))
			{
				$headerValues[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headerValues[0]) ?? $headerValues[0];
			}
			$this->validateHeaders($name, $headerValues, $dataset['columns']);
			$records = [];
			$keys = [];
			while(($record = $this->readCsvRecord($handle, $raw, $line, $dataset['delimiter'])) !== null)
			{
				$values = $record['values'];
				if($values === [null])
				{
					continue;
				}
				if(count($values) !== count($headerValues))
				{
					throw new RuntimeException($name . ' row ' . $record['rowNumber'] . ' has ' . count($values) . ' fields; expected ' . count($headerValues) . '.');
				}
				$strings = array_map(static fn(mixed $value): string => (string) $value, $values);
				$sourceRow = array_combine($headerValues, $strings);
				if($sourceRow === false)
				{
					throw new RuntimeException('Unable to map dataset row ' . $record['rowNumber'] . ' for ' . $name . '.');
				}
				$row = [];
				foreach($dataset['columns'] as $canonical => $sourceHeader)
				{
					$row[$canonical] = $sourceRow[$sourceHeader];
				}
				if(!$this->matchesWhere($row, $dataset['where']))
				{
					continue;
				}
				foreach($dataset['required'] as $required)
				{
					if($row[$required] === '')
					{
						throw new RuntimeException($name . ' row ' . $record['rowNumber'] . ' is missing required value ' . $required . '.');
					}
				}
				$key = $row[$dataset['key']];
				if($key === '')
				{
					throw new RuntimeException($name . ' row ' . $record['rowNumber'] . ' has an empty key.');
				}
				if(isset($keys[$key]))
				{
					throw new RuntimeException($name . ' row ' . $record['rowNumber'] . ' duplicates key ' . $key . ' from row ' . $keys[$key] . '.');
				}
				$keys[$key] = $record['rowNumber'];
				$records[] = ['row' => $row, 'sourceRow' => $sourceRow, 'rowNumber' => $record['rowNumber']];
			}
			return $records;
		}
		finally
		{
			fclose($handle);
		}
	}

	/** Read one CSV record and retain its starting physical line number. */
	private function readCsvRecord(mixed $handle, string $raw, int &$line, string $delimiter): ?array
	{
		$start = ftell($handle);
		if($start === false)
		{
			throw new RuntimeException('Unable to read the CSV stream position.');
		}
		$values = fgetcsv($handle, null, $delimiter, '"', '');
		if($values === false)
		{
			return null;
		}
		$end = ftell($handle);
		if($end === false)
		{
			throw new RuntimeException('Unable to read the CSV stream position.');
		}
		$rowNumber = $line;
		$sourceRecord = substr($raw, $start, $end - $start);
		$line += substr_count($sourceRecord, "\n");
		if(substr_count($sourceRecord, '"') % 2 !== 0)
		{
			throw new RuntimeException('CSV record starting on row ' . $rowNumber . ' contains an unclosed quoted field.');
		}
		return ['values' => $values, 'rowNumber' => $rowNumber];
	}

	/** Require nonempty unique headers and all configured source columns. */
	private function validateHeaders(string $dataset, array $headers, array $columns): void
	{
		$seen = [];
		foreach($headers as $header)
		{
			if($header === '')
			{
				throw new RuntimeException('Dataset ' . $dataset . ' contains an empty header.');
			}
			if(isset($seen[$header]))
			{
				throw new RuntimeException('Dataset ' . $dataset . ' contains duplicate header: ' . $header);
			}
			$seen[$header] = true;
		}
		foreach($columns as $source)
		{
			if(!isset($seen[$source]))
			{
				throw new RuntimeException('Dataset ' . $dataset . ' is missing configured source header: ' . $source);
			}
		}
	}

	/** Test a mapped row against exact configured string values. */
	private function matchesWhere(array $row, array $where): bool
	{
		foreach($where as $column => $expected)
		{
			if($row[$column] !== $expected)
			{
				return false;
			}
		}
		return true;
	}

	/** Interpolate safe row values into a configured Markdown output path. */
	private function rowOutput(string $pattern, array $row, string $dataset, int $rowNumber): string
	{
		$output = preg_replace_callback('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', function(array $match) use ($row, $dataset, $rowNumber): string {
			$column = $match[1];
			if(!array_key_exists($column, $row))
			{
				throw new RuntimeException('Dataset ' . $dataset . ' output references unknown column: ' . $column);
			}
			$value = $row[$column];
			if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,254}$/', $value) || $value === '.' || $value === '..')
			{
				throw new RuntimeException($dataset . ' row ' . $rowNumber . ' has an unsafe output segment in ' . $column . '.');
			}
			return $value;
		}, $pattern);
		if($output === null || str_contains($output, '{') || str_contains($output, '}'))
		{
			throw new RuntimeException('Dataset ' . $dataset . ' contains an invalid output placeholder.');
		}
		return $this->validateOutputPath($output, $dataset . ' row ' . $rowNumber);
	}

	/** Normalize a relative Markdown path and reject traversal or ambiguous forms. */
	private function validateOutputPath(string $path, string $label): string
	{
		if($path === '' || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/') || !str_ends_with(strtolower($path), '.md'))
		{
			throw new RuntimeException('Invalid Markdown output path for ' . $label . ': ' . $path);
		}
		$parts = explode('/', $path);
		foreach($parts as $part)
		{
			if($part === '' || $part === '.' || $part === '..')
			{
				throw new RuntimeException('Output paths cannot be empty or traverse directories: ' . $path);
			}
		}
		return implode('/', $parts);
	}

	/** Reserve one case-sensitive output path and report collisions. */
	private function reserveOutput(array &$outputs, string $path, string $owner): void
	{
		$key = mb_strtolower($path, 'UTF-8');
		if(array_key_exists($key, $outputs))
		{
			throw new RuntimeException('Generated output collision at ' . $path . ' between ' . $outputs[$key] . ' and ' . $owner . '.');
		}
		$outputs[$key] = $owner;
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

	/** Resolve an existing regular file without allowing project-root escape. */
	private function resolveInputFile(string $path, string $label, bool $allowAbsolute = false): string
	{
		if($path === '' || str_contains($path, "\0") || str_contains($path, '\\'))
		{
			throw new RuntimeException($label . ' path is invalid.');
		}
		$isAbsolute = str_starts_with($path, '/');
		if($isAbsolute && !$allowAbsolute)
		{
			throw new RuntimeException($label . ' path must be project-relative: ' . $path);
		}
		$candidate = $isAbsolute ? $path : $this->projectRoot . '/' . $path;
		$this->rejectSymlinkSegments($candidate, $label);
		$resolved = realpath($candidate);
		if($resolved === false || !is_file($resolved) || !$this->isWithinProject($resolved))
		{
			throw new RuntimeException($label . ' must be a regular file inside the project: ' . $path);
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
		$candidate = str_starts_with($path, '/') ? $path : $this->projectRoot . '/' . $path;
		$candidate = rtrim($candidate, '/');
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
			$entries = array_values(array_diff(scandir($target) ?: [], ['.', '..']));
			if($entries !== [])
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
			if(!is_string($key) || !in_array($key, $allowed, true))
			{
				throw new RuntimeException('Unknown ' . $label . ' field: ' . (string) $key);
			}
		}
	}

	/** Normalize a YAML list whose members must be nonempty unique strings. */
	private function stringList(mixed $value, string $label): array
	{
		if(!is_array($value) || ($value !== [] && !self::isList($value)))
		{
			throw new RuntimeException(ucfirst($label) . ' must be a list.');
		}
		$strings = [];
		foreach($value as $entry)
		{
			if(!is_string($entry) || $entry === '')
			{
				throw new RuntimeException(ucfirst($label) . ' must contain nonempty strings.');
			}
			$strings[] = $entry;
		}
		if(count($strings) !== count(array_unique($strings)))
		{
			throw new RuntimeException(ucfirst($label) . ' cannot contain duplicates.');
		}
		return $strings;
	}

	/** Report whether an array uses consecutive integer keys. */
	private static function isList(array $value): bool
	{
		return array_is_list($value);
	}
}
