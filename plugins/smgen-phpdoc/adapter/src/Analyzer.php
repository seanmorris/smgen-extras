<?php

declare(strict_types=1);

namespace SmgenPhpDoc;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UnionType;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;
use Throwable;

/**
 * Builds the versioned documentation graph from PHP syntax trees.
 */
final class Analyzer
{
	private const MAX_SOURCE_BYTES = 67_108_864;
	private const MAX_FILE_BYTES = 8_388_608;

	private readonly DocBlockReader $docs;
	private readonly Standard $printer;
	private readonly NodeFinder $finder;
	private array $diagnostics = [];
	private array $symbols = [];
	private string $root;
	private array $visibility;

	/** Construct reusable parser helpers without loading analyzed application code. */
	public function __construct()
	{
		$this->docs = new DocBlockReader();
		$this->printer = new Standard();
		$this->finder = new NodeFinder();
	}

	/**
	 * Validate adapter input and return one deterministic graph.
	 *
	 * @param array<string,mixed> $input Normalized input supplied by the Node CLI.
	 * @return array<string,mixed>
	 */
	public function analyze(array $input): array
	{
		$this->root = $this->root($input['root'] ?? null);
		$this->visibility = $this->visibility($input['visibility'] ?? ['public']);
		$target = $input['php'] ?? '8.2';
		if(!is_string($target) || !preg_match('/^\d+\.\d+$/', $target))
		{
			throw new RuntimeException('Adapter PHP target must look like 8.2.');
		}
		try
		{
			$parser = (new ParserFactory())->createForVersion(PhpVersion::fromString($target));
		}
		catch(Throwable $error)
		{
			throw new RuntimeException('Unsupported PHP target ' . $target . ': ' . $error->getMessage(), 0, $error);
		}

		$files = $this->files($input['files'] ?? null);
		$totalBytes = 0;
		foreach($files as $file)
		{
			$source = file_get_contents($file['absolute']);
			if($source === false)
			{
				throw new RuntimeException('Unable to read PHP source: ' . $file['relative']);
			}
			$bytes = strlen($source);
			$totalBytes += $bytes;
			if($bytes > self::MAX_FILE_BYTES || $totalBytes > self::MAX_SOURCE_BYTES)
			{
				throw new RuntimeException('Configured PHP source exceeds the adapter size limit.');
			}
			try
			{
				$statements = $parser->parse($source) ?? [];
			}
			catch(Throwable $error)
			{
				throw new RuntimeException($file['relative'] . ': ' . $error->getMessage(), 0, $error);
			}
			$traverser = new NodeTraverser();
			$traverser->addVisitor(new NameResolver(null, [
				'preserveOriginalNames' => true,
				'replaceNodes' => false,
			]));
			$statements = $traverser->traverse($statements);
			$this->analyzeStatements($statements, $source, $file['relative']);
		}

		usort($this->symbols, static fn(array $left, array $right): int => strcmp($left['id'], $right['id']));
		$this->rejectDuplicateIds();
		$this->attachInheritedDocumentation();
		$this->normalizeEmptyMappings();
		usort($this->diagnostics, static function(array $left, array $right): int {
			return [$left['file'] ?? '', $left['line'] ?? 0, $left['code'], $left['message']]
				<=> [$right['file'] ?? '', $right['line'] ?? 0, $right['code'], $right['message']];
		});

		$package = $input['package'] ?? null;
		if(!is_array($package)) throw new RuntimeException('Adapter input requires package metadata.');
		return [
			'schemaVersion' => 1,
			'package' => [
				'title' => (string) ($package['title'] ?? ''),
				'description' => (string) ($package['description'] ?? ''),
				'version' => (string) ($package['version'] ?? ''),
			],
			'diagnostics' => $this->diagnostics,
			'symbols' => $this->symbols,
		];
	}

	/** Analyze global and namespaced top-level statements in one source file. */
	private function analyzeStatements(array $statements, string $source, string $file): void
	{
		$hasNamespace = false;
		foreach($statements as $statement)
		{
			if(!$statement instanceof Namespace_) continue;
			$hasNamespace = true;
			$namespace = $statement->name?->toString() ?? '';
			$this->analyzeNamespace($statement->stmts, $namespace, $source, $file);
		}
		if(!$hasNamespace)
		{
			$this->analyzeNamespace($statements, '', $source, $file);
		}
	}

	/** Analyze direct and conditional declarations under one namespace. */
	private function analyzeNamespace(array $statements, string $namespace, string $source, string $file): void
	{
		$imports = $this->imports($statements);
		foreach($statements as $statement)
		{
			if($statement instanceof Use_ || $statement instanceof GroupUse) continue;
			if($statement instanceof ClassLike)
			{
				$this->classLike($statement, $namespace, $imports, $source, $file, false);
				continue;
			}
			if($statement instanceof Function_)
			{
				$this->functionSymbol($statement, $namespace, $imports, $source, $file, false);
				continue;
			}
			if($statement instanceof Const_)
			{
				$this->constantSymbols($statement, $namespace, $imports, $source, $file, false);
				continue;
			}
			$this->conditionalDeclarations($statement, $namespace, $imports, $source, $file);
		}

		foreach($this->finder->findInstanceOf($statements, Expr\FuncCall::class) as $call)
		{
			if($call->name instanceof Name && strtolower($call->name->toString()) === 'class_alias')
			{
				$this->diagnostic('warning', 'runtime.class-alias', 'class_alias() defines runtime-only API surface.', $file, $call->getStartLine());
			}
		}
		foreach($this->finder->findInstanceOf($statements, Expr\Eval_::class) as $eval)
		{
			$this->diagnostic('warning', 'runtime.eval', 'eval() may define API surface that static analysis cannot discover.', $file, $eval->getStartLine());
		}
	}

	/** Find declarations nested under control flow and mark them as conditional. */
	private function conditionalDeclarations(
		Stmt $statement,
		string $namespace,
		array $imports,
		string $source,
		string $file,
	): void
	{
		foreach($this->finder->findInstanceOf([$statement], ClassLike::class) as $classLike)
		{
			$this->classLike($classLike, $namespace, $imports, $source, $file, true);
		}
		foreach($this->finder->findInstanceOf([$statement], Function_::class) as $function)
		{
			$this->functionSymbol($function, $namespace, $imports, $source, $file, true);
		}
		foreach($this->finder->findInstanceOf([$statement], Const_::class) as $constant)
		{
			$this->constantSymbols($constant, $namespace, $imports, $source, $file, true);
		}
	}

	/** Convert one named class, interface, trait, or enum into a graph symbol. */
	private function classLike(
		ClassLike $node,
		string $namespace,
		array $imports,
		string $source,
		string $file,
		bool $conditional,
	): void
	{
		if($node->name === null)
		{
			$this->diagnostic('info', 'runtime.anonymous-class', 'Anonymous classes do not have stable API names and are omitted.', $file, $node->getStartLine());
			return;
		}
		$name = isset($node->namespacedName) ? $node->namespacedName->toString() : ($namespace === '' ? $node->name->toString() : $namespace . '\\' . $node->name);
		$kind = $node instanceof Interface_ ? 'interface' : ($node instanceof Trait_ ? 'trait' : ($node instanceof Enum_ ? 'enum' : 'class'));
		$parentName = $node instanceof Class_ && $node->extends !== null ? $this->resolvedName($node->extends) : null;
		$docs = $this->docs->read($node->getDocComment()?->getText(), $namespace, $imports, $name, $parentName, $this->diagnostics, $file, $node->getStartLine());
		$extends = [];
		$implements = [];
		if($node instanceof Class_ && $node->extends !== null)
		{
			$extends[] = $this->nativeType($node->extends, $namespace, $imports, $name, $parentName);
		}
		elseif($node instanceof Interface_)
		{
			foreach($node->extends as $extended) $extends[] = $this->nativeType($extended, $namespace, $imports, $name, $parentName);
		}
		if($node instanceof Class_ || $node instanceof Enum_)
		{
			foreach($node->implements as $implemented) $implements[] = $this->nativeType($implemented, $namespace, $imports, $name, $parentName);
		}

		$members = [];
		$traits = [];
		foreach($node->stmts as $statement)
		{
			if($statement instanceof ClassMethod && $this->includedVisibility($this->memberVisibility($statement)))
			{
				$members[] = $this->method($statement, $name, $namespace, $imports, $parentName, $file);
				if(strtolower($statement->name->toString()) === '__construct')
				{
					foreach($this->promotedProperties($statement, $name, $namespace, $imports, $parentName, $file) as $property)
					{
						$members[] = $property;
					}
				}
			}
			elseif($statement instanceof Property && $this->includedVisibility($this->memberVisibility($statement)))
			{
				foreach($this->properties($statement, $name, $namespace, $imports, $parentName, $file) as $property) $members[] = $property;
			}
			elseif($statement instanceof ClassConst && $this->includedVisibility($this->memberVisibility($statement)))
			{
				foreach($this->classConstants($statement, $name, $namespace, $imports, $parentName, $file) as $constant) $members[] = $constant;
			}
			elseif($statement instanceof EnumCase)
			{
				$members[] = $this->enumCase($statement, $name, $namespace, $imports, $parentName, $file);
			}
			elseif($statement instanceof TraitUse)
			{
				$traits[] = $this->traitUse($statement);
			}
		}
		foreach($docs['magicProperties'] as $property)
		{
			$memberName = (string) ($property['name'] ?? 'property');
			$members[] = $this->virtualMember($name, 'property', $memberName, $property, $file, $node->getStartLine());
		}
		foreach($docs['magicMethods'] as $method)
		{
			$memberName = (string) ($method['name'] ?? 'method');
			$members[] = $this->virtualMember($name, 'method', $memberName, $method, $file, $node->getStartLine());
		}
		usort($members, static fn(array $left, array $right): int => [$left['kind'], strtolower($left['name']), $left['name']] <=> [$right['kind'], strtolower($right['name']), $right['name']]);

		$this->symbols[] = [
			'id' => 'php:' . $kind . ':' . $name,
			'kind' => $kind,
			'name' => $name,
			'shortName' => $node->name->toString(),
			'namespace' => $namespace,
			'signature' => $this->classSignature($node),
			'flags' => $this->classFlags($node),
			'attributes' => $this->attributes($node->attrGroups),
			'docs' => $docs,
			'extends' => $extends,
			'implements' => $implements,
			'traits' => $traits,
			'members' => $members,
			'source' => $this->sourceSpan($file, $node),
			'conditional' => $conditional,
		];
		if($conditional)
		{
			$this->diagnostic('warning', 'runtime.conditional-declaration', $name . ' is declared conditionally and may not exist at runtime.', $file, $node->getStartLine(), 'php:' . $kind . ':' . $name);
		}
	}

	/** Convert one namespaced or global function into a graph symbol. */
	private function functionSymbol(
		Function_ $node,
		string $namespace,
		array $imports,
		string $source,
		string $file,
		bool $conditional,
	): void
	{
		$name = isset($node->namespacedName) ? $node->namespacedName->toString() : ($namespace === '' ? $node->name->toString() : $namespace . '\\' . $node->name);
		$docs = $this->docs->read($node->getDocComment()?->getText(), $namespace, $imports, $name, null, $this->diagnostics, $file, $node->getStartLine());
		$parameters = $this->parameters($node->params, $docs, $namespace, $imports, $name, null);
		$member = [
			'id' => 'php:function:' . $name,
			'kind' => 'function',
			'name' => $name,
			'shortName' => $node->name->toString(),
			'namespace' => $namespace,
			'signature' => $this->functionSignature($node),
			'flags' => $node->byRef ? ['returns-reference'] : [],
			'attributes' => $this->attributes($node->attrGroups),
			'docs' => $docs,
			'extends' => [],
			'implements' => [],
			'traits' => [],
			'members' => [],
			'parameters' => $parameters,
			'returnType' => $this->nativeType($node->returnType, $namespace, $imports, $name, null),
			'source' => $this->sourceSpan($file, $node),
			'conditional' => $conditional,
		];
		$this->symbols[] = $member;
		if($conditional)
		{
			$this->diagnostic('warning', 'runtime.conditional-declaration', $name . ' is declared conditionally and may not exist at runtime.', $file, $node->getStartLine(), $member['id']);
		}
	}

	/** Convert each namespace constant in one statement into a graph symbol. */
	private function constantSymbols(
		Const_ $node,
		string $namespace,
		array $imports,
		string $source,
		string $file,
		bool $conditional,
	): void
	{
		$docs = $this->docs->read($node->getDocComment()?->getText(), $namespace, $imports, '', null, $this->diagnostics, $file, $node->getStartLine());
		foreach($node->consts as $constant)
		{
			$name = $namespace === '' ? $constant->name->toString() : $namespace . '\\' . $constant->name;
			$id = 'php:constant:' . $name;
			$this->symbols[] = [
				'id' => $id,
				'kind' => 'constant',
				'name' => $name,
				'shortName' => $constant->name->toString(),
				'namespace' => $namespace,
				'signature' => 'const ' . $constant->name . ' = ' . $this->printer->prettyPrintExpr($constant->value) . ';',
				'flags' => [],
				'attributes' => $this->attributes($node->attrGroups),
				'docs' => $docs,
				'extends' => [],
				'implements' => [],
				'traits' => [],
				'members' => [],
				'source' => $this->sourceSpan($file, $constant),
				'conditional' => $conditional,
			];
			if($conditional)
			{
				$this->diagnostic('warning', 'runtime.conditional-declaration', $name . ' is declared conditionally and may not exist at runtime.', $file, $node->getStartLine(), $id);
			}
		}
	}

	/** Convert one declared method and all parameter metadata. */
	private function method(
		ClassMethod $node,
		string $owner,
		string $namespace,
		array $imports,
		?string $parent,
		string $file,
	): array
	{
		$name = $node->name->toString();
		$docs = $this->docs->read($node->getDocComment()?->getText(), $namespace, $imports, $owner, $parent, $this->diagnostics, $file, $node->getStartLine());
		return [
			'id' => 'php:method:' . $owner . '::' . $name,
			'kind' => 'method',
			'name' => $name,
			'anchor' => $this->anchor('method', $name),
			'signature' => $this->methodSignature($node),
			'flags' => $this->methodFlags($node),
			'type' => null,
			'returnType' => $this->nativeType($node->returnType, $namespace, $imports, $owner, $parent),
			'parameters' => $this->parameters($node->params, $docs, $namespace, $imports, $owner, $parent),
			'docs' => $docs,
			'attributes' => $this->attributes($node->attrGroups),
			'source' => $this->sourceSpan($file, $node),
			'virtual' => false,
		];
	}

	/** Convert constructor-promoted parameters into independently linkable properties. */
	private function promotedProperties(
		ClassMethod $constructor,
		string $owner,
		string $namespace,
		array $imports,
		?string $parent,
		string $file,
	): array
	{
		$result = [];
		$constructorDocs = $this->docs->read($constructor->getDocComment()?->getText(), $namespace, $imports, $owner, $parent, $this->diagnostics, $file, $constructor->getStartLine());
		foreach($constructor->params as $parameter)
		{
			if($parameter->flags === 0 || !$parameter->var instanceof Expr\Variable || !is_string($parameter->var->name)) continue;
			$visibility = $this->paramVisibility($parameter);
			if(!$this->includedVisibility($visibility)) continue;
			$name = $parameter->var->name;
			$documentation = $this->docs->emptyDocumentation();
			if(isset($constructorDocs['params'][$name]))
			{
				$documentation['summary'] = $constructorDocs['params'][$name]['description'];
				$documentation['var'] = $constructorDocs['params'][$name];
			}
			$result[] = [
				'id' => 'php:property:' . $owner . '::$' . $name,
				'kind' => 'property',
				'name' => $name,
				'anchor' => $this->anchor('property', $name),
				'signature' => $this->promotedPropertySignature($parameter),
				'flags' => array_values(array_filter([$visibility, ($parameter->flags & Class_::MODIFIER_READONLY) !== 0 ? 'readonly' : '', 'promoted'])),
				'type' => $this->nativeType($parameter->type, $namespace, $imports, $owner, $parent),
				'returnType' => null,
				'parameters' => [],
				'docs' => $documentation,
				'attributes' => $this->attributes($parameter->attrGroups),
				'source' => $this->sourceSpan($file, $parameter),
				'virtual' => false,
			];
		}
		return $result;
	}

	/** Convert all properties declared by one PHP property statement. */
	private function properties(
		Property $node,
		string $owner,
		string $namespace,
		array $imports,
		?string $parent,
		string $file,
	): array
	{
		$result = [];
		$docs = $this->docs->read($node->getDocComment()?->getText(), $namespace, $imports, $owner, $parent, $this->diagnostics, $file, $node->getStartLine());
		foreach($node->props as $property)
		{
			$name = $property->name->toString();
			$result[] = [
				'id' => 'php:property:' . $owner . '::$' . $name,
				'kind' => 'property',
				'name' => $name,
				'anchor' => $this->anchor('property', $name),
				'signature' => $this->propertySignature($node, $property),
				'flags' => $this->propertyFlags($node),
				'type' => $this->nativeType($node->type, $namespace, $imports, $owner, $parent),
				'returnType' => null,
				'parameters' => [],
				'docs' => $docs,
				'attributes' => $this->attributes($node->attrGroups),
				'source' => $this->sourceSpan($file, $property),
				'virtual' => false,
			];
		}
		return $result;
	}

	/** Convert all constants declared by one class constant statement. */
	private function classConstants(
		ClassConst $node,
		string $owner,
		string $namespace,
		array $imports,
		?string $parent,
		string $file,
	): array
	{
		$result = [];
		$docs = $this->docs->read($node->getDocComment()?->getText(), $namespace, $imports, $owner, $parent, $this->diagnostics, $file, $node->getStartLine());
		foreach($node->consts as $constant)
		{
			$name = $constant->name->toString();
			$result[] = [
				'id' => 'php:constant:' . $owner . '::' . $name,
				'kind' => 'constant',
				'name' => $name,
				'anchor' => $this->anchor('constant', $name),
				'signature' => $this->classConstantSignature($node, $constant),
				'flags' => $this->classConstantFlags($node),
				'type' => $this->nativeType($node->type, $namespace, $imports, $owner, $parent),
				'returnType' => null,
				'parameters' => [],
				'docs' => $docs,
				'attributes' => $this->attributes($node->attrGroups),
				'source' => $this->sourceSpan($file, $constant),
				'virtual' => false,
			];
		}
		return $result;
	}

	/** Convert one enum case into a graph member. */
	private function enumCase(
		EnumCase $node,
		string $owner,
		string $namespace,
		array $imports,
		?string $parent,
		string $file,
	): array
	{
		$name = $node->name->toString();
		return [
			'id' => 'php:case:' . $owner . '::' . $name,
			'kind' => 'case',
			'name' => $name,
			'anchor' => $this->anchor('case', $name),
			'signature' => 'case ' . $name . ($node->expr === null ? '' : ' = ' . $this->printer->prettyPrintExpr($node->expr)) . ';',
			'flags' => [],
			'type' => null,
			'returnType' => null,
			'parameters' => [],
			'docs' => $this->docs->read($node->getDocComment()?->getText(), $namespace, $imports, $owner, $parent, $this->diagnostics, $file, $node->getStartLine()),
			'attributes' => $this->attributes($node->attrGroups),
			'source' => $this->sourceSpan($file, $node),
			'virtual' => false,
		];
	}

	/** Convert a documented magic method or property into a virtual member. */
	private function virtualMember(string $owner, string $kind, string $name, array $value, string $file, int $line): array
	{
		$documentation = $this->docs->emptyDocumentation();
		$documentation['summary'] = trim((string) ($value['description'] ?? ''));
		$type = $kind === 'property' ? ($value['type'] ?? null) : null;
		$returnType = $kind === 'method' ? ($value['returnType'] ?? null) : null;
		return [
			'id' => 'php:' . $kind . ':' . $owner . '::' . ($kind === 'property' ? '$' : '') . $name,
			'kind' => $kind,
			'name' => $name,
			'anchor' => $this->anchor($kind, $name),
			'signature' => (string) ($value['signature'] ?? ($kind === 'property' ? '$' . $name : $name . '()')),
			'flags' => array_values(array_filter([
				'virtual',
				($value['static'] ?? false) ? 'static' : '',
				($value['readable'] ?? false) ? 'readable' : '',
				($value['writable'] ?? false) ? 'writable' : '',
			])),
			'type' => $type,
			'returnType' => $returnType,
			'parameters' => $value['parameters'] ?? [],
			'docs' => $documentation,
			'attributes' => [],
			'source' => ['file' => $file, 'startLine' => $line, 'endLine' => $line],
			'virtual' => true,
		];
	}

	/** Normalize parameters and attach corresponding PHPDoc prose. */
	private function parameters(
		array $parameters,
		array $documentation,
		string $namespace,
		array $imports,
		string $owner,
		?string $parent,
	): array
	{
		$result = [];
		foreach($parameters as $parameter)
		{
			if(!$parameter->var instanceof Expr\Variable || !is_string($parameter->var->name)) continue;
			$name = $parameter->var->name;
			$result[] = [
				'name' => $name,
				'type' => $this->nativeType($parameter->type, $namespace, $imports, $owner, $parent),
				'byReference' => $parameter->byRef,
				'variadic' => $parameter->variadic,
				'default' => $parameter->default === null ? null : $this->printer->prettyPrintExpr($parameter->default),
				'docs' => $documentation['params'][$name] ?? null,
				'attributes' => $this->attributes($parameter->attrGroups),
			];
		}
		return $result;
	}

	/** Convert trait names and conflict adaptations without applying runtime behavior. */
	private function traitUse(TraitUse $node): array
	{
		$adaptations = [];
		foreach($node->adaptations as $adaptation)
		{
			if($adaptation instanceof Alias)
			{
				$adaptations[] = [
					'kind' => 'alias',
					'trait' => $adaptation->trait?->toString(),
					'method' => $adaptation->method->toString(),
					'alias' => $adaptation->newName?->toString(),
					'visibility' => $this->modifierVisibility($adaptation->newModifier),
					'line' => $adaptation->getStartLine(),
				];
			}
			elseif($adaptation instanceof Precedence)
			{
				$adaptations[] = [
					'kind' => 'precedence',
					'trait' => $adaptation->trait->toString(),
					'method' => $adaptation->method->toString(),
					'insteadOf' => array_map(static fn(Name $name): string => $name->toString(), $adaptation->insteadof),
					'line' => $adaptation->getStartLine(),
				];
			}
		}
		return [
			'types' => array_map(
				fn(Name $name): array => [
					'display' => $name->toString(),
					'references' => [$this->resolvedName($name)],
				],
				$node->traits,
			),
			'adaptations' => $adaptations,
			'line' => $node->getStartLine(),
		];
	}

	/** Collect namespace class imports for resolving PHPDoc type names. */
	private function imports(array $statements): array
	{
		$result = [];
		foreach($statements as $statement)
		{
			if($statement instanceof Use_ && $statement->type === Use_::TYPE_NORMAL)
			{
				foreach($statement->uses as $use)
				{
					if($use->type !== Use_::TYPE_UNKNOWN && $use->type !== Use_::TYPE_NORMAL) continue;
					$name = $this->resolvedName($use->name);
					$alias = $use->alias?->toString() ?? $use->name->getLast();
					$result[strtolower($alias)] = $name;
				}
			}
			elseif($statement instanceof GroupUse && $statement->type === Use_::TYPE_NORMAL)
			{
				foreach($statement->uses as $use)
				{
					if($use->type !== Use_::TYPE_UNKNOWN && $use->type !== Use_::TYPE_NORMAL) continue;
					$name = $statement->prefix->toString() . '\\' . $use->name->toString();
					$alias = $use->alias?->toString() ?? $use->name->getLast();
					$result[strtolower($alias)] = $name;
				}
			}
		}
		ksort($result, SORT_STRING);
		return $result;
	}

	/** Normalize one native type while retaining its source spelling and targets. */
	private function nativeType(Node|string|null $type, string $namespace, array $imports, string $owner, ?string $parent): ?array
	{
		if($type === null) return null;
		$display = $this->typeText($type);
		$normalized = $this->docs->type($display, $namespace, $imports, $owner, $parent);
		$references = [];
		$this->nativeReferences($type, $references, $owner, $parent);
		if($references !== [])
		{
			$normalized['references'] = array_values(array_unique($references));
			sort($normalized['references'], SORT_STRING);
		}
		return $normalized;
	}

	/** Collect canonical names attached by php-parser's static name resolver. */
	private function nativeReferences(Node|string $type, array &$references, string $owner, ?string $parent): void
	{
		if($type instanceof Name)
		{
			$name = strtolower($type->toString());
			if(in_array($name, ['self', 'static'], true)) $references[] = $owner;
			elseif($name === 'parent' && $parent !== null) $references[] = $parent;
			else $references[] = $this->resolvedName($type);
			return;
		}
		if($type instanceof NullableType)
		{
			$this->nativeReferences($type->type, $references, $owner, $parent);
		}
		elseif($type instanceof UnionType || $type instanceof IntersectionType)
		{
			foreach($type->types as $part) $this->nativeReferences($part, $references, $owner, $parent);
		}
	}

	/** Render one native type node without changing author-facing aliases. */
	private function typeText(Node|string $type): string
	{
		if(is_string($type)) return $type;
		if($type instanceof Name || $type instanceof Identifier) return $type->toString();
		if($type instanceof NullableType) return '?' . $this->typeText($type->type);
		if($type instanceof UnionType) return implode('|', array_map(fn(Node $part): string => $this->typeText($part), $type->types));
		if($type instanceof IntersectionType) return implode('&', array_map(fn(Node $part): string => $this->typeText($part), $type->types));
		return trim($this->printer->prettyPrint([$type]));
	}

	/** Render one class-like declaration line without its body or PHPDoc. */
	private function classSignature(ClassLike $node): string
	{
		$parts = $this->classFlags($node);
		$parts[] = $node instanceof Interface_ ? 'interface' : ($node instanceof Trait_ ? 'trait' : ($node instanceof Enum_ ? 'enum' : 'class'));
		$parts[] = $node->name?->toString() ?? 'anonymous';
		$signature = implode(' ', $parts);
		if($node instanceof Enum_ && $node->scalarType !== null) $signature .= ': ' . $this->typeText($node->scalarType);
		if($node instanceof Class_ && $node->extends !== null) $signature .= ' extends ' . $node->extends->toString();
		if($node instanceof Interface_ && $node->extends !== []) $signature .= ' extends ' . implode(', ', array_map(static fn(Name $name): string => $name->toString(), $node->extends));
		if(($node instanceof Class_ || $node instanceof Enum_) && $node->implements !== []) $signature .= ' implements ' . implode(', ', array_map(static fn(Name $name): string => $name->toString(), $node->implements));
		return $signature;
	}

	/** Render one free function signature without its executable body. */
	private function functionSignature(Function_ $node): string
	{
		$parameters = implode(', ', array_map(fn(Param $parameter): string => $this->parameterSignature($parameter), $node->params));
		return 'function ' . ($node->byRef ? '&' : '') . $node->name . '(' . $parameters . ')'
			. ($node->returnType === null ? '' : ': ' . $this->typeText($node->returnType));
	}

	/** Render one class method signature without its executable body. */
	private function methodSignature(ClassMethod $node): string
	{
		$parameters = implode(', ', array_map(fn(Param $parameter): string => $this->parameterSignature($parameter), $node->params));
		return implode(' ', $this->methodFlags($node)) . ' function ' . ($node->byRef ? '&' : '') . $node->name . '(' . $parameters . ')'
			. ($node->returnType === null ? '' : ': ' . $this->typeText($node->returnType));
	}

	/** Render one ordinary or promoted parameter declaration. */
	private function parameterSignature(Param $parameter): string
	{
		$parts = [];
		if($parameter->flags !== 0)
		{
			$parts[] = $this->paramVisibility($parameter);
			if(($parameter->flags & Class_::MODIFIER_READONLY) !== 0) $parts[] = 'readonly';
		}
		if($parameter->type !== null) $parts[] = $this->typeText($parameter->type);
		$name = $parameter->var instanceof Expr\Variable && is_string($parameter->var->name) ? $parameter->var->name : 'value';
		$parts[] = ($parameter->byRef ? '&' : '') . ($parameter->variadic ? '...' : '') . '$' . $name;
		$signature = implode(' ', array_filter($parts));
		if($parameter->default !== null) $signature .= ' = ' . $this->printer->prettyPrintExpr($parameter->default);
		return $signature;
	}

	/** Render a promoted parameter as its effective property declaration. */
	private function promotedPropertySignature(Param $parameter): string
	{
		$name = $parameter->var instanceof Expr\Variable && is_string($parameter->var->name) ? $parameter->var->name : 'value';
		return $this->paramVisibility($parameter) . ' '
			. (($parameter->flags & Class_::MODIFIER_READONLY) !== 0 ? 'readonly ' : '')
			. ($parameter->type === null ? '' : $this->typeText($parameter->type) . ' ')
			. '$' . $name . ($parameter->default === null ? '' : ' = ' . $this->printer->prettyPrintExpr($parameter->default)) . ';';
	}

	/** Render one property declaration from a possibly multi-property statement. */
	private function propertySignature(Property $statement, Stmt\PropertyProperty $property): string
	{
		return implode(' ', $this->propertyFlags($statement)) . ' '
			. ($statement->type === null ? '' : $this->typeText($statement->type) . ' ')
			. '$' . $property->name
			. ($property->default === null ? '' : ' = ' . $this->printer->prettyPrintExpr($property->default)) . ';';
	}

	/** Render one class constant from a possibly multi-constant statement. */
	private function classConstantSignature(ClassConst $statement, Node\Const_ $constant): string
	{
		return implode(' ', $this->classConstantFlags($statement)) . ' const '
			. ($statement->type === null ? '' : $this->typeText($statement->type) . ' ')
			. $constant->name . ' = ' . $this->printer->prettyPrintExpr($constant->value) . ';';
	}

	/** Return semantic class modifiers in PHP declaration order. */
	private function classFlags(ClassLike $node): array
	{
		$flags = [];
		if($node instanceof Class_ && $node->isFinal()) $flags[] = 'final';
		if($node instanceof Class_ && $node->isAbstract()) $flags[] = 'abstract';
		if($node instanceof Class_ && $node->isReadonly()) $flags[] = 'readonly';
		return $flags;
	}

	/** Return method visibility and behavioral modifiers in declaration order. */
	private function methodFlags(ClassMethod $node): array
	{
		return array_values(array_filter([
			$this->memberVisibility($node),
			$node->isFinal() ? 'final' : '',
			$node->isAbstract() ? 'abstract' : '',
			$node->isStatic() ? 'static' : '',
			$node->byRef ? 'returns-reference' : '',
		]));
	}

	/** Return property visibility and mutability modifiers. */
	private function propertyFlags(Property $node): array
	{
		return array_values(array_filter([
			$this->memberVisibility($node),
			$node->isStatic() ? 'static' : '',
			$node->isReadonly() ? 'readonly' : '',
		]));
	}

	/** Return class constant visibility and finality modifiers. */
	private function classConstantFlags(ClassConst $node): array
	{
		return array_values(array_filter([
			$this->memberVisibility($node),
			$node->isFinal() ? 'final' : '',
		]));
	}

	/** Read the effective visibility of a class member. */
	private function memberVisibility(ClassMethod|Property|ClassConst $node): string
	{
		if($node->isPrivate()) return 'private';
		if($node->isProtected()) return 'protected';
		return 'public';
	}

	/** Read visibility flags from a promoted constructor parameter. */
	private function paramVisibility(Param $parameter): string
	{
		if(($parameter->flags & Class_::MODIFIER_PRIVATE) !== 0) return 'private';
		if(($parameter->flags & Class_::MODIFIER_PROTECTED) !== 0) return 'protected';
		return 'public';
	}

	/** Convert one raw visibility modifier into readable graph text. */
	private function modifierVisibility(?int $modifier): ?string
	{
		if($modifier === null) return null;
		if(($modifier & Class_::MODIFIER_PRIVATE) !== 0) return 'private';
		if(($modifier & Class_::MODIFIER_PROTECTED) !== 0) return 'protected';
		if(($modifier & Class_::MODIFIER_PUBLIC) !== 0) return 'public';
		return null;
	}

	/** Report whether configured visibility includes one declaration. */
	private function includedVisibility(string $visibility): bool
	{
		return in_array($visibility, $this->visibility, true);
	}

	/** Convert attribute groups into readable names and unevaluated arguments. */
	private function attributes(array $groups): array
	{
		$result = [];
		foreach($groups as $group)
		{
			if(!$group instanceof AttributeGroup) continue;
			foreach($group->attrs as $attribute)
			{
				$result[] = [
					'name' => $this->resolvedName($attribute->name),
					'arguments' => array_map(fn(Arg $argument): string => ($argument->name === null ? '' : $argument->name . ': ') . $this->printer->prettyPrintExpr($argument->value), $attribute->args),
				];
			}
		}
		return $result;
	}

	/** Read the canonical target attached to one resolved syntax name. */
	private function resolvedName(Name $name): string
	{
		$resolved = $name->getAttribute('resolvedName');
		return $resolved instanceof Name ? $resolved->toString() : ltrim($name->toString(), '\\');
	}

	/** Build a project-relative source span for a declaration. */
	private function sourceSpan(string $file, Node $node): array
	{
		return [
			'file' => $file,
			'startLine' => max(1, $node->getStartLine()),
			'endLine' => max(1, $node->getEndLine()),
		];
	}

	/** Generate a stable lower-kebab member anchor. */
	private function anchor(string $kind, string $name): string
	{
		$value = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $name) ?? $name;
		$value = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? $value, '-'));
		return $kind . '-' . ($value === '' ? substr(hash('sha256', $name), 0, 12) : $value);
	}

	/** Add one normalized diagnostic with optional symbol ownership. */
	private function diagnostic(string $severity, string $code, string $message, string $file, int $line, ?string $symbolId = null): void
	{
		$value = compact('severity', 'code', 'message', 'file', 'line');
		if($symbolId !== null) $value['symbolId'] = $symbolId;
		$this->diagnostics[] = $value;
	}

	/** Validate and canonicalize the project root supplied across the process boundary. */
	private function root(mixed $value): string
	{
		if(!is_string($value) || $value === '') throw new RuntimeException('Adapter input requires a project root.');
		$root = realpath($value);
		if($root === false || !is_dir($root)) throw new RuntimeException('Adapter project root does not exist: ' . $value);
		return rtrim($root, DIRECTORY_SEPARATOR);
	}

	/** Validate configured public and protected visibility values. */
	private function visibility(mixed $value): array
	{
		if(!is_array($value) || $value === []) throw new RuntimeException('Adapter visibility must be a nonempty list.');
		$result = [];
		foreach($value as $item)
		{
			if(!is_string($item) || !in_array($item, ['public', 'protected'], true)) throw new RuntimeException('Adapter supports public and protected visibility only.');
			$result[] = $item;
		}
		return array_values(array_unique($result));
	}

	/** Validate every input file against the project root and link boundary. */
	private function files(mixed $value): array
	{
		if(!is_array($value) || $value === []) throw new RuntimeException('Adapter input requires PHP files.');
		$result = [];
		foreach($value as $index => $file)
		{
			if(!is_array($file) || !is_string($file['absolute'] ?? null) || !is_string($file['relative'] ?? null))
			{
				throw new RuntimeException('Invalid adapter file at index ' . $index . '.');
			}
			$absolute = $file['absolute'];
			if(is_link($absolute)) throw new RuntimeException('Adapter refuses symbolic-link source: ' . $absolute);
			$real = realpath($absolute);
			if($real === false || !is_file($real) || !$this->insideRoot($real)) throw new RuntimeException('Adapter source escapes the project root: ' . $absolute);
			$relative = str_replace('\\', '/', $file['relative']);
			$expected = str_replace('\\', '/', substr($real, strlen($this->root) + 1));
			if($relative !== $expected || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'php') throw new RuntimeException('Adapter source path does not match its project-relative path: ' . $relative);
			$result[] = ['absolute' => $real, 'relative' => $relative];
		}
		usort($result, static fn(array $left, array $right): int => strcmp($left['relative'], $right['relative']));
		return $result;
	}

	/** Report whether a canonical path is inside the configured project root. */
	private function insideRoot(string $path): bool
	{
		return $path === $this->root || str_starts_with($path, $this->root . DIRECTORY_SEPARATOR);
	}

	/** Reject duplicate symbol or member IDs before rendering routes or links. */
	private function rejectDuplicateIds(): void
	{
		$ids = [];
		foreach($this->symbols as $symbol)
		{
			foreach(array_merge([$symbol], $symbol['members']) as $item)
			{
				$id = $item['id'];
				if(isset($ids[$id])) throw new RuntimeException('Duplicate documentation graph ID: ' . $id);
				$ids[$id] = true;
			}
		}
	}

	/** Attach inherited docs separately when an internal ancestor declares a member. */
	private function attachInheritedDocumentation(): void
	{
		$symbols = [];
		foreach($this->symbols as $index => $symbol) $symbols[$symbol['name']] = $index;
		foreach($this->symbols as &$symbol)
		{
			if(!in_array($symbol['kind'], ['class', 'interface'], true)) continue;
			$ancestors = [];
			foreach(array_merge($symbol['extends'], $symbol['implements']) as $relationship)
			{
				foreach($relationship['references'] as $reference) $ancestors[] = $reference;
			}
			foreach($symbol['members'] as &$member)
			{
				if(($member['docs']['summary'] ?? '') !== '') continue;
				foreach($ancestors as $ancestor)
				{
					if(!isset($symbols[$ancestor])) continue;
					$ancestorSymbol = $this->symbols[$symbols[$ancestor]];
					foreach($ancestorSymbol['members'] as $ancestorMember)
					{
						if($ancestorMember['kind'] === $member['kind'] && strtolower($ancestorMember['name']) === strtolower($member['name']) && ($ancestorMember['docs']['summary'] ?? '') !== '')
						{
							$member['inheritedDocs'] = ['from' => $ancestorMember['id'], 'docs' => $ancestorMember['docs']];
							continue 3;
						}
					}
				}
			}
			unset($member);
		}
		unset($symbol);
	}

	/** Convert empty associative fields into JSON objects at the process boundary. */
	private function normalizeEmptyMappings(): void
	{
		foreach($this->symbols as &$symbol)
		{
			if(($symbol['docs']['params'] ?? null) === []) $symbol['docs']['params'] = (object) [];
			foreach($symbol['members'] as &$member)
			{
				if(($member['docs']['params'] ?? null) === []) $member['docs']['params'] = (object) [];
				if(isset($member['inheritedDocs']['docs']) && ($member['inheritedDocs']['docs']['params'] ?? null) === [])
				{
					$member['inheritedDocs']['docs']['params'] = (object) [];
				}
			}
			unset($member);
		}
		unset($symbol);
	}
}
