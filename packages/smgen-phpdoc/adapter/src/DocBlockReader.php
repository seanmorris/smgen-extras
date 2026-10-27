<?php

declare(strict_types=1);

namespace SmgenPhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\DeprecatedTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use Throwable;

/**
 * Converts PHPDoc comments into stable data without relying on target code.
 */
final class DocBlockReader
{
	private Lexer $lexer;
	private PhpDocParser $parser;

	/** Construct the maintained PHPDoc parser used for rich type syntax. */
	public function __construct()
	{
		$config = new ParserConfig(usedAttributes: []);
		$constantParser = new ConstExprParser($config);
		$this->lexer = new Lexer($config);
		$this->parser = new PhpDocParser($config, new TypeParser($config, $constantParser), $constantParser);
	}

	/**
	 * Parse one optional comment in its namespace and import context.
	 *
	 * @param array<string,string> $imports Lowercase alias to qualified name.
	 * @param list<array<string,mixed>> $diagnostics Mutable adapter diagnostics.
	 * @return array<string,mixed>
	 */
	public function read(
		?string $comment,
		string $namespace,
		array $imports,
		string $symbol,
		?string $parent,
		array &$diagnostics,
		string $file,
		int $line,
	): array
	{
		$result = $this->emptyDocumentation();
		if($comment === null || trim($comment) === '')
		{
			return $result;
		}

		try
		{
			$document = $this->parser->parse(new TokenIterator($this->lexer->tokenize($comment)));
		}
		catch(Throwable $error)
		{
			$diagnostics[] = [
				'severity' => 'warning',
				'code' => 'phpdoc.parse',
				'message' => 'Unable to parse PHPDoc: ' . $error->getMessage(),
				'file' => $file,
				'line' => $line,
			];
			return $result;
		}

		$text = [];
		foreach($document->children as $child)
		{
			if($child instanceof PhpDocTextNode)
			{
				$text[] = $child->text;
				continue;
			}
			if(!$child instanceof PhpDocTagNode)
			{
				continue;
			}
			$name = strtolower(ltrim($child->name, '@'));
			$value = $child->value;
			$result['tags'][] = ['name' => $name, 'value' => trim((string) $value)];
			if(in_array($name, ['param', 'phpstan-param', 'psalm-param'], true) && $value instanceof ParamTagValueNode)
			{
				$key = ltrim($value->parameterName, '$');
				$result['params'][$key] = [
					'type' => $this->type((string) $value->type, $namespace, $imports, $symbol, $parent),
					'description' => trim($value->description),
				];
			}
			elseif(in_array($name, ['return', 'phpstan-return', 'psalm-return'], true) && $value instanceof ReturnTagValueNode)
			{
				$result['returns'] = [
					'type' => $this->type((string) $value->type, $namespace, $imports, $symbol, $parent),
					'description' => trim($value->description),
				];
			}
			elseif($name === 'throws' && $value instanceof ThrowsTagValueNode)
			{
				$result['throws'][] = [
					'type' => $this->type((string) $value->type, $namespace, $imports, $symbol, $parent),
					'description' => trim($value->description),
				];
			}
			elseif($name === 'var' && $value instanceof VarTagValueNode)
			{
				$result['var'] = [
					'type' => $this->type((string) $value->type, $namespace, $imports, $symbol, $parent),
					'description' => trim($value->description),
				];
			}
			elseif($name === 'deprecated' && $value instanceof DeprecatedTagValueNode)
			{
				$result['deprecated'] = trim($value->description) ?: 'Deprecated.';
			}
			elseif($name === 'see')
			{
				$result['see'][] = trim((string) $value);
			}
			elseif($name === 'method' && $value instanceof MethodTagValueNode)
			{
				$result['magicMethods'][] = $this->magicMethod($value, $namespace, $imports, $symbol, $parent);
			}
			elseif(in_array($name, ['property', 'property-read', 'property-write'], true) && $value instanceof PropertyTagValueNode)
			{
				$result['magicProperties'][] = [
					'name' => ltrim($value->propertyName, '$'),
					'type' => $this->type((string) $value->type, $namespace, $imports, $symbol, $parent),
					'description' => trim($value->description),
					'readable' => $name !== 'property-write',
					'writable' => $name !== 'property-read',
				];
			}
		}

		$prose = trim(implode("\n\n", $text));
		$parts = preg_split('/\R\s*\R/', $prose, 2) ?: [];
		$result['summary'] = trim($parts[0] ?? '');
		$result['description'] = trim($parts[1] ?? '');
		return $result;
	}

	/**
	 * Normalize a native or PHPDoc type and resolve every named component.
	 *
	 * @param array<string,string> $imports Lowercase alias to qualified name.
	 * @return array{display:string,references:list<string>}
	 */
	public function type(
		string $display,
		string $namespace,
		array $imports,
		string $symbol,
		?string $parent,
	): array
	{
		$references = [];
		preg_match_all('/(?<![$A-Za-z0-9_\\\\])\\\\?[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*/', $display, $matches);
		foreach($matches[0] ?? [] as $candidate)
		{
			$resolved = $this->resolveTypeName((string) $candidate, $namespace, $imports, $symbol, $parent);
			if($resolved !== null)
			{
				$references[$resolved] = true;
			}
		}
		$names = array_keys($references);
		sort($names, SORT_STRING);
		return ['display' => trim($display), 'references' => $names];
	}

	/** Return the stable empty documentation structure used by undocumented nodes. */
	public function emptyDocumentation(): array
	{
		return [
			'summary' => '',
			'description' => '',
			'params' => [],
			'returns' => null,
			'throws' => [],
			'var' => null,
			'deprecated' => '',
			'see' => [],
			'tags' => [],
			'magicMethods' => [],
			'magicProperties' => [],
		];
	}

	/**
	 * Normalize one documented virtual method, including its parameters.
	 *
	 * @param array<string,string> $imports Lowercase alias to qualified name.
	 * @return array<string,mixed>
	 */
	private function magicMethod(
		MethodTagValueNode $method,
		string $namespace,
		array $imports,
		string $symbol,
		?string $parent,
	): array
	{
		$parameters = [];
		foreach($method->parameters as $parameter)
		{
			$parameters[] = [
				'name' => ltrim($parameter->parameterName, '$'),
				'type' => $parameter->type === null
					? null
					: $this->type((string) $parameter->type, $namespace, $imports, $symbol, $parent),
				'byReference' => $parameter->isReference,
				'variadic' => $parameter->isVariadic,
				'default' => $parameter->defaultValue === null ? null : (string) $parameter->defaultValue,
			];
		}
		$returnType = $method->returnType === null
			? null
			: $this->type((string) $method->returnType, $namespace, $imports, $symbol, $parent);
		return [
			'name' => $method->methodName,
			'static' => $method->isStatic,
			'returnType' => $returnType,
			'parameters' => $parameters,
			'description' => trim($method->description),
			'signature' => trim((string) $method),
		];
	}

	/** Resolve one type token against imports and declaration-relative names. */
	private function resolveTypeName(
		string $name,
		string $namespace,
		array $imports,
		string $symbol,
		?string $parent,
	): ?string
	{
		$builtins = [
			'array', 'bool', 'boolean', 'callable', 'class-string', 'closed-resource',
			'false', 'float', 'int', 'integer', 'iterable', 'list', 'literal-string',
			'mixed', 'never', 'non-empty-array', 'non-empty-list', 'non-empty-string',
			'null', 'numeric-string', 'object', 'open-resource', 'positive-int',
			'resource', 'scalar', 'string', 'true', 'void', 'key-of', 'value-of',
		];
		$lower = strtolower(ltrim($name, '\\'));
		if(in_array($lower, $builtins, true) || strlen($name) === 1 && ctype_upper($name))
		{
			return null;
		}
		if(in_array($lower, ['self', 'static', '$this'], true)) return $symbol !== '' ? $symbol : null;
		if($lower === 'parent') return $parent;
		if(str_starts_with($name, '\\')) return ltrim($name, '\\');

		$parts = explode('\\', $name);
		$alias = strtolower($parts[0]);
		if(isset($imports[$alias]))
		{
			array_shift($parts);
			return $imports[$alias] . ($parts === [] ? '' : '\\' . implode('\\', $parts));
		}
		return $namespace === '' ? $name : $namespace . '\\' . $name;
	}
}
