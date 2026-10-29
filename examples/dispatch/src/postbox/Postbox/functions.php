<?php

declare(strict_types=1);

namespace Postbox;

/**
 * Build an address from a header string.
 *
 * A short alias for {@see Address::parse()} that reads well in fluent code.
 *
 * @param non-empty-string $header Address such as `Mara Bell <mara@example.test>`.
 * @return Address Parsed address.
 */
function address(string $header): Address
{
	return Address::parse($header);
}

/**
 * Strip HTML down to a plain-text alternative.
 *
 * @param string $html HTML body.
 * @return string Text with tags removed and whitespace collapsed.
 */
function textFromHtml(string $html): string
{
	$text = html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	return trim(preg_replace('/[ \t]+/', ' ', $text) ?? $text);
}
