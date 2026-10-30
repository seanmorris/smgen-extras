<?php

declare(strict_types=1);

namespace SmgenStore;

use RuntimeException;

/**
 * Finds store pages and validates their public static checkout metadata.
 */
final class StoreValidator
{
	private readonly string $projectRoot;

	/** Resolve the canonical project root used to constrain every scanned path. */
	public function __construct(string $projectRoot)
	{
		$root = realpath($projectRoot);
		if($root === false || !is_dir($root))
		{
			throw new RuntimeException('Project root does not exist: ' . $projectRoot);
		}
		$this->projectRoot = rtrim($root, DIRECTORY_SEPARATOR);
	}

	/**
	 * Validate Markdown files below the supplied project paths.
	 *
	 * @param list<string> $paths Project-relative or contained absolute paths.
	 */
	public function validatePaths(array $paths): int
	{
		$files = [];
		foreach($paths as $path)
		{
			$resolved = $this->resolvePath($path);
			if(is_file($resolved))
			{
				if($this->isMarkdown($resolved))
				{
					$files[] = $resolved;
				}
				continue;
			}
			$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($resolved, \FilesystemIterator::SKIP_DOTS));
			foreach($iterator as $file)
			{
				if($file->isLink())
				{
					throw new RuntimeException('Store validation cannot traverse a symbolic link: ' . $file->getPathname());
				}
				if($file->isFile() && $this->isMarkdown($file->getPathname()))
				{
					$files[] = $file->getPathname();
				}
			}
		}
		sort($files);
		$count = 0;
		foreach(array_values(array_unique($files)) as $file)
		{
			if($this->validateFile($file))
			{
				$count++;
			}
		}
		return $count;
	}

	/** Parse one Markdown file and validate it when store metadata is present. */
	public function validateFile(string $path): bool
	{
		$metadata = $this->frontmatter($path);
		$hasStore = array_key_exists('store', $metadata);
		if(($metadata['storePage'] ?? false) === true && !$hasStore)
		{
			throw new RuntimeException($path . ': storePage requires a store mapping.');
		}
		if(!$hasStore)
		{
			return false;
		}
		if(($metadata['storePage'] ?? null) !== true)
		{
			throw new RuntimeException($path . ': store metadata requires storePage: true.');
		}
		$this->validateStore($metadata['store'], $path);
		return true;
	}

	/** Validate the compact, versioned product and checkout mapping. */
	private function validateStore(mixed $store, string $path): void
	{
		if(!is_array($store) || self::isList($store))
		{
			throw new RuntimeException($path . ': store must be a mapping.');
		}
		$this->rejectSecrets($store, 'store', $path);
		$this->rejectUnknown($store, ['schema', 'type', 'id', 'name', 'description', 'price', 'checkout'], 'store', $path);
		if(($store['schema'] ?? null) !== 1)
		{
			throw new RuntimeException($path . ': store.schema must be the integer 1.');
		}
		if(($store['type'] ?? null) !== 'product')
		{
			throw new RuntimeException($path . ': store.type must be product.');
		}
		$this->requireString($store, 'id', 'store', $path, '/^[A-Za-z0-9][A-Za-z0-9._:-]{0,199}$/');
		$this->requireString($store, 'name', 'store', $path, null, 200);
		if(isset($store['description']))
		{
			$this->requireString($store, 'description', 'store', $path, null, 2000);
		}
		$this->validatePrice($store['price'] ?? null, $path);
		$this->validateCheckout($store['checkout'] ?? null, $path);
	}

	/** Validate decimal display price and ISO-style uppercase currency metadata. */
	private function validatePrice(mixed $price, string $path): void
	{
		if(!is_array($price) || self::isList($price))
		{
			throw new RuntimeException($path . ': store.price must be a mapping.');
		}
		$this->rejectUnknown($price, ['amount', 'currency'], 'store.price', $path);
		$amount = $this->requireString($price, 'amount', 'store.price', $path, '/^(?:0|[1-9][0-9]*)(?:\.[0-9]{1,6})?$/', 64);
		if(!preg_match('/[1-9]/', $amount))
		{
			throw new RuntimeException($path . ': store.price.amount must be greater than zero.');
		}
		$this->requireString($price, 'currency', 'store.price', $path, '/^[A-Z]{3}$/');
	}

	/** Validate provider capabilities and the public values required by one mode. */
	private function validateCheckout(mixed $checkout, string $path): void
	{
		if(!is_array($checkout) || self::isList($checkout))
		{
			throw new RuntimeException($path . ': store.checkout must be a mapping.');
		}
		$this->rejectUnknown($checkout, ['provider', 'presentation', 'url', 'label', 'public'], 'store.checkout', $path);
		$provider = $this->requireString($checkout, 'provider', 'store.checkout', $path, '/^(hosted-link|stripe|paypal|square)$/');
		$presentation = $this->requireString($checkout, 'presentation', 'store.checkout', $path, '/^(link|embed|cart)$/');
		$url = $this->requireString($checkout, 'url', 'store.checkout', $path, null, 2048);
		$this->validateHttpsUrl($url, $path);
		if(isset($checkout['label']))
		{
			$this->requireString($checkout, 'label', 'store.checkout', $path, null, 120);
		}
		$public = $checkout['public'] ?? [];
		if(!is_array($public) || ($public !== [] && self::isList($public)))
		{
			throw new RuntimeException($path . ': store.checkout.public must be a mapping.');
		}

		if($presentation === 'link')
		{
			if($public !== [])
			{
				throw new RuntimeException($path . ': link checkout does not accept public provider fields.');
			}
			return;
		}
		if(in_array($provider, ['hosted-link', 'square'], true))
		{
			throw new RuntimeException($path . ': ' . $provider . ' supports link presentation only.');
		}
		if($provider === 'stripe')
		{
			if($presentation !== 'embed')
			{
				throw new RuntimeException($path . ': Stripe supports link or embed presentation.');
			}
			$this->rejectUnknown($public, ['buyButtonId', 'publishableKey'], 'store.checkout.public', $path);
			$this->requireString($public, 'buyButtonId', 'store.checkout.public', $path, '/^buy_btn_[A-Za-z0-9_]+$/', 200);
			$this->requireString($public, 'publishableKey', 'store.checkout.public', $path, '/^pk_(?:test|live)_[A-Za-z0-9_]+$/', 255);
			return;
		}
		if($presentation === 'embed')
		{
			$this->rejectUnknown($public, ['hostedButtonId', 'clientId'], 'store.checkout.public', $path);
			$this->requireString($public, 'hostedButtonId', 'store.checkout.public', $path, '/^[A-Za-z0-9_-]{6,200}$/');
			$this->requireString($public, 'clientId', 'store.checkout.public', $path, '/^[A-Za-z0-9_-]{20,255}$/');
			return;
		}
		$this->rejectUnknown($public, ['merchantId', 'cartItemId'], 'store.checkout.public', $path);
		$this->requireString($public, 'merchantId', 'store.checkout.public', $path, '/^[A-Za-z0-9_-]{6,200}$/');
		$this->requireString($public, 'cartItemId', 'store.checkout.public', $path, '/^[A-Za-z0-9_-]{6,200}$/');
	}

	/** Require an HTTPS URL without credentials, fragments, or control bytes. */
	private function validateHttpsUrl(string $url, string $path): void
	{
		$parts = parse_url($url);
		if(
			$parts === false
			|| filter_var($url, FILTER_VALIDATE_URL) === false
			|| ($parts['scheme'] ?? null) !== 'https'
			|| !isset($parts['host'])
			|| $parts['host'] === ''
			|| isset($parts['user'])
			|| isset($parts['pass'])
			|| isset($parts['fragment'])
			|| preg_match('/[\x00-\x20\x7F]/', $url)
		)
		{
			throw new RuntimeException($path . ': store.checkout.url must be an HTTPS URL without credentials, fragments, or whitespace.');
		}
	}

	/** Reject credential-shaped field names and known private-key value prefixes. */
	private function rejectSecrets(mixed $value, string $field, string $path): void
	{
		if(is_string($value) && preg_match('/^(?:sk_(?:live|test)_|sq0atp-|sq0csp-)/', $value))
		{
			throw new RuntimeException($path . ': ' . $field . ' appears to contain a secret credential.');
		}
		if(!is_array($value))
		{
			return;
		}
		foreach($value as $key => $child)
		{
			$keyString = (string) $key;
			if(preg_match('/(?:secret|private.?key|access.?token|api.?key)/i', $keyString))
			{
				throw new RuntimeException($path . ': secret field is forbidden in public store metadata: ' . $field . '.' . $keyString);
			}
			$this->rejectSecrets($child, $field . '.' . $keyString, $path);
		}
	}

	/** Return one required string after optional pattern and length checks. */
	private function requireString(array $mapping, string $field, string $context, string $path, ?string $pattern = null, int $maxLength = 255): string
	{
		$value = $mapping[$field] ?? null;
		if(!is_string($value) || $value === '' || mb_strlen($value, 'UTF-8') > $maxLength || ($pattern !== null && !preg_match($pattern, $value)))
		{
			throw new RuntimeException($path . ': ' . $context . '.' . $field . ' is missing or invalid.');
		}
		return $value;
	}

	/** Parse leading Pandoc YAML frontmatter into a mapping. */
	private function frontmatter(string $path): array
	{
		$contents = file_get_contents($path);
		if($contents === false)
		{
			throw new RuntimeException('Unable to read Markdown page: ' . $path);
		}
		if(!mb_check_encoding($contents, 'UTF-8'))
		{
			throw new RuntimeException($path . ': page is not valid UTF-8.');
		}
		$contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
		if(!preg_match('/\A---[ \t]*\R/', $contents))
		{
			return [];
		}
		if(!preg_match('/\A---[ \t]*\R(.*?)(?:\R---[ \t]*|\R\.\.\.[ \t]*)\R/s', $contents, $match))
		{
			throw new RuntimeException($path . ': YAML frontmatter is missing its closing delimiter.');
		}
		set_error_handler(static function(int $severity, string $message) use ($path): never {
			throw new RuntimeException($path . ': invalid YAML frontmatter: ' . $message);
		});
		try
		{
			$value = yaml_parse($match[1]);
		}
		finally
		{
			restore_error_handler();
		}
		if($value === null)
		{
			return [];
		}
		if(!is_array($value) || self::isList($value))
		{
			throw new RuntimeException($path . ': frontmatter must be a YAML mapping.');
		}
		return $value;
	}

	/** Resolve one file or directory while rejecting project escape and links. */
	private function resolvePath(string $path): string
	{
		if($path === '' || str_contains($path, "\0") || str_contains($path, '\\'))
		{
			throw new RuntimeException('Store validation path is invalid.');
		}
		$candidate = str_starts_with($path, '/') ? $path : $this->projectRoot . '/' . $path;
		if(!$this->isWithinProject($candidate))
		{
			throw new RuntimeException('Store validation path escapes the project root: ' . $path);
		}
		$relative = ltrim(substr($candidate, strlen($this->projectRoot)), DIRECTORY_SEPARATOR);
		$current = $this->projectRoot;
		foreach($relative === '' ? [] : explode(DIRECTORY_SEPARATOR, $relative) as $part)
		{
			if($part === '' || $part === '.' || $part === '..')
			{
				throw new RuntimeException('Store validation path contains an invalid segment: ' . $path);
			}
			$current .= DIRECTORY_SEPARATOR . $part;
			if(is_link($current))
			{
				throw new RuntimeException('Store validation path cannot contain a symbolic link: ' . $current);
			}
		}
		$resolved = realpath($candidate);
		if($resolved === false || (!$this->isWithinProject($resolved)) || (!is_file($resolved) && !is_dir($resolved)))
		{
			throw new RuntimeException('Store validation path does not exist inside the project: ' . $path);
		}
		return $resolved;
	}

	/** Report whether an absolute path is contained by the canonical project root. */
	private function isWithinProject(string $path): bool
	{
		return $path === $this->projectRoot || str_starts_with($path, $this->projectRoot . DIRECTORY_SEPARATOR);
	}

	/** Report whether a source file has a supported Markdown suffix. */
	private function isMarkdown(string $path): bool
	{
		return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['md', 'markdown'], true);
	}

	/** Reject unrecognized keys within one versioned metadata mapping. */
	private function rejectUnknown(array $mapping, array $allowed, string $context, string $path): void
	{
		foreach(array_keys($mapping) as $key)
		{
			if(!is_string($key) || !in_array($key, $allowed, true))
			{
				throw new RuntimeException($path . ': unknown ' . $context . ' field: ' . (string) $key);
			}
		}
	}

	/** Report whether an array uses consecutive integer keys. */
	private static function isList(array $value): bool
	{
		return array_is_list($value);
	}
}
