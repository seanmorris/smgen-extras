<?php

declare(strict_types=1);

use SmgenStore\StoreValidator;

require_once(dirname(__DIR__) . '/src/autoload.php');

/**
 * Exercises store schemas, provider capabilities, and Pandoc output regressions.
 */
final class StoreRegression
{
	private string $tempRoot;
	private string $filter;

	/** Create isolated fixtures, run validations, and remove temporary pages. */
	public function run(): void
	{
		$this->tempRoot = sys_get_temp_dir() . '/smgen-store-' . bin2hex(random_bytes(6));
		$this->filter = dirname(__DIR__) . '/filters/store.lua';
		mkdir($this->tempRoot, 0700, true);
		try
		{
			$this->testSupportedProviderModesValidate();
			$this->testUnsafeAndUnsupportedMetadataFails();
			$this->testMalformedFrontmatterFails();
			$this->testLinkModeLoadsNoProviderCode();
			$this->testStripeScriptIsAddedOnce();
			$this->testPayPalModesLoadOnlyTheirDependencies();
		}
		finally
		{
			$this->removeTree($this->tempRoot);
		}
		fwrite(STDOUT, "smgen-store tests passed.\n");
	}

	/** Reject a page that starts a YAML block but never closes it. */
	private function testMalformedFrontmatterFails(): void
	{
		$path = $this->tempRoot . '/unclosed.md';
		file_put_contents($path, "---\ntitle: Broken\n# Still YAML\n");
		$this->assertFails(static fn() => (new StoreValidator(dirname($path)))->validateFile($path), 'missing its closing delimiter');
	}

	/** Accept each capability supported by the built-in static adapters. */
	private function testSupportedProviderModesValidate(): void
	{
		$pages = $this->directory('valid');
		$modes = [
			['hosted-link', 'link', []],
			['stripe', 'link', []],
			['stripe', 'embed', ['buyButtonId' => 'buy_btn_123456', 'publishableKey' => 'pk_test_123456789']],
			['paypal', 'link', []],
			['paypal', 'embed', ['hostedButtonId' => 'BUTTON123', 'clientId' => 'CLIENT_ID_12345678901234567890']],
			['paypal', 'cart', ['merchantId' => 'MERCHANT123', 'cartItemId' => 'ITEM123']],
			['square', 'link', []],
		];
		foreach($modes as $index => [$provider, $presentation, $public])
		{
			$this->writePage($pages . '/' . $index . '.md', $this->metadata($provider, $presentation, $public));
		}
		$count = (new StoreValidator($this->tempRoot))->validatePaths(['valid']);
		$this->assertSame(count($modes), $count, 'valid provider count');
	}

	/** Reject secrets, numeric prices, HTTP fallbacks, and unsupported adapters. */
	private function testUnsafeAndUnsupportedMetadataFails(): void
	{
		$cases = [];
		$numeric = $this->metadata('hosted-link', 'link', []);
		$numeric['store']['price']['amount'] = 24.00;
		$cases[] = [$numeric, 'amount is missing or invalid'];
		$http = $this->metadata('hosted-link', 'link', []);
		$http['store']['checkout']['url'] = 'http://example.com/pay';
		$cases[] = [$http, 'must be an HTTPS URL'];
		$square = $this->metadata('square', 'embed', []);
		$cases[] = [$square, 'square supports link presentation only'];
		$secret = $this->metadata('stripe', 'embed', ['buyButtonId' => 'buy_btn_123456', 'publishableKey' => 'pk_test_123456789']);
		$secret['store']['checkout']['public']['clientSecret'] = 'hidden';
		$cases[] = [$secret, 'secret field is forbidden'];
		$secretValue = $this->metadata('hosted-link', 'link', []);
		$secretValue['store']['description'] = 'sk_live_privatevalue';
		$cases[] = [$secretValue, 'appears to contain a secret credential'];

		foreach($cases as $index => [$metadata, $message])
		{
			$path = $this->tempRoot . '/invalid-' . $index . '.md';
			$this->writePage($path, $metadata);
			$this->assertFails(static fn() => (new StoreValidator(dirname($path)))->validateFile($path), $message);
		}
	}

	/** Confirm the zero-JavaScript link path preserves arbitrary Markdown HTML. */
	private function testLinkModeLoadsNoProviderCode(): void
	{
		$path = $this->tempRoot . '/link.md';
		$this->writePage($path, $this->metadata('hosted-link', 'link', []), "<span data-raw=\"yes\">Trusted HTML</span>\n\n::: {.smgen-store-checkout}\n:::\n");
		$html = $this->pandoc($path);
		$this->assertContains('class="smgen-store-button smgen-store-button--hosted-link"', $html, 'hosted link');
		$this->assertContains('<span data-raw="yes">Trusted HTML</span>', $html, 'raw HTML');
		$this->assertExcludes('<script', $html, 'link scripts');
	}

	/** Add one Stripe script even when a page contains two checkout markers. */
	private function testStripeScriptIsAddedOnce(): void
	{
		$path = $this->tempRoot . '/stripe.md';
		$body = "::: {.smgen-store-checkout}\n:::\n\n::: {.smgen-store-checkout}\n:::\n";
		$this->writePage($path, $this->metadata('stripe', 'embed', [
			'buyButtonId' => 'buy_btn_123456',
			'publishableKey' => 'pk_test_123456789',
		]), $body);
		$html = $this->pandoc($path);
		$this->assertContains('<stripe-buy-button', $html, 'Stripe component');
		$this->assertSame(1, substr_count($html, 'https://js.stripe.com/v3/buy-button.js'), 'Stripe script count');
		$this->assertExcludes('paypal.com/sdk', $html, 'Stripe PayPal exclusion');
	}

	/** Emit only the SDK, markup, and local initializer required by each PayPal mode. */
	private function testPayPalModesLoadOnlyTheirDependencies(): void
	{
		$hosted = $this->tempRoot . '/paypal-hosted.md';
		$this->writePage($hosted, $this->metadata('paypal', 'embed', [
			'hostedButtonId' => 'BUTTON123',
			'clientId' => 'CLIENT_ID_12345678901234567890',
		]));
		$hostedHtml = $this->pandoc($hosted);
		$this->assertContains('components=hosted-buttons', $hostedHtml, 'PayPal hosted SDK');
		$this->assertContains('/manual/smgen-store/store.js', $hostedHtml, 'local initializer URL');
		$this->assertExcludes('ncp/cart/cart.js', $hostedHtml, 'hosted cart exclusion');

		$cart = $this->tempRoot . '/paypal-cart.md';
		$this->writePage($cart, $this->metadata('paypal', 'cart', [
			'merchantId' => 'MERCHANT123',
			'cartItemId' => 'ITEM123',
		]));
		$cartHtml = $this->pandoc($cart);
		$this->assertContains('ncp/cart/cart.js', $cartHtml, 'PayPal cart SDK');
		$this->assertContains('data-id="ITEM123"', $cartHtml, 'PayPal cart item');
		$this->assertContains('<paypal-cart-button', $cartHtml, 'PayPal view cart');
		$this->assertExcludes('components=hosted-buttons', $cartHtml, 'cart hosted exclusion');
	}

	/** Build valid base metadata for one provider capability test. */
	private function metadata(string $provider, string $presentation, array $public): array
	{
		$checkout = [
			'provider' => $provider,
			'presentation' => $presentation,
			'url' => 'https://example.com/pay',
		];
		if($public !== [])
		{
			$checkout['public'] = $public;
		}
		return [
			'title' => 'Test Product',
			'storePage' => true,
			'plugins' => ['smgen-store' => true],
			'store' => [
				'schema' => 1,
				'type' => 'product',
				'id' => 'TEST-001',
				'name' => 'Test Product',
				'description' => 'A product used by the regression suite.',
				'price' => ['amount' => '24.00', 'currency' => 'USD'],
				'checkout' => $checkout,
			],
		];
	}

	/** Write one complete Markdown page with YAML metadata and a checkout marker. */
	private function writePage(string $path, array $metadata, string $body = "::: {.smgen-store-checkout}\n:::\n"): void
	{
		$yaml = yaml_emit($metadata, YAML_UTF8_ENCODING, YAML_LN_BREAK);
		if($yaml === false || file_put_contents($path, $yaml . "\n" . $body) === false)
		{
			throw new RuntimeException('Unable to write store fixture: ' . $path);
		}
	}

	/** Run the package filter through Pandoc and return its HTML fragment. */
	private function pandoc(string $path): string
	{
		$oldBase = getenv('BASE_URL');
		putenv('BASE_URL=/manual');
		$process = proc_open(
			['pandoc', '--from=markdown', '--to=html', '--lua-filter=' . $this->filter, $path],
			[0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
			$pipes,
		);
		if(!is_resource($process))
		{
			throw new RuntimeException('Unable to start Pandoc.');
		}
		fclose($pipes[0]);
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$status = proc_close($process);
		$oldBase === false ? putenv('BASE_URL') : putenv('BASE_URL=' . $oldBase);
		if($status !== 0 || $stdout === false)
		{
			throw new RuntimeException('Pandoc failed: ' . (string) $stderr);
		}
		return $stdout;
	}

	/** Create one named directory inside the private test root. */
	private function directory(string $name): string
	{
		$path = $this->tempRoot . '/' . $name;
		mkdir($path, 0700, true);
		return $path;
	}

	/** Assert that a callback fails with an expected diagnostic fragment. */
	private function assertFails(callable $callback, string $expected): void
	{
		try
		{
			$callback();
		}
		catch(Throwable $error)
		{
			$this->assertContains($expected, $error->getMessage(), 'failure message');
			return;
		}
		throw new RuntimeException('Expected callback to fail with: ' . $expected);
	}

	/** Assert strict equality and retain a useful label in failures. */
	private function assertSame(mixed $expected, mixed $actual, string $label): void
	{
		if($expected !== $actual)
		{
			throw new RuntimeException($label . ' mismatch: ' . var_export($actual, true));
		}
	}

	/** Assert that generated text contains one exact fragment. */
	private function assertContains(string $needle, string $haystack, string $label): void
	{
		if(!str_contains($haystack, $needle))
		{
			throw new RuntimeException($label . ' does not contain ' . $needle . ': ' . $haystack);
		}
	}

	/** Assert that generated text omits one exact fragment. */
	private function assertExcludes(string $needle, string $haystack, string $label): void
	{
		if(str_contains($haystack, $needle))
		{
			throw new RuntimeException($label . ' unexpectedly contains ' . $needle . '.');
		}
	}

	/** Remove temporary fixtures without following symbolic links. */
	private function removeTree(string $path): void
	{
		if(!file_exists($path) && !is_link($path))
		{
			return;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST,
		);
		foreach($iterator as $entry)
		{
			if($entry->isLink() || $entry->isFile())
			{
				unlink($entry->getPathname());
			}
			else
			{
				rmdir($entry->getPathname());
			}
		}
		rmdir($path);
	}
}

(new StoreRegression())->run();
