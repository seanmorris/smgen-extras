<?php

declare(strict_types=1);

namespace Acme;

/** Default number of widget retries. */
const WIDGET_RETRIES = 3;

/**
 * Create a widget with default options.
 *
 * @param string $name Public widget name.
 * @return Widget New widget instance.
 */
function widget(string $name): Widget
{
	return new Widget($name);
}

if(PHP_VERSION_ID > 0)
{
	/**
	 * Report runtime build data.
	 *
	 * @return string Runtime build data.
	 */
	function runtime_build(): string
	{
		return PHP_VERSION;
	}
}

class_alias(Widget::class, 'Acme\\LegacyWidget');

throw new \RuntimeException('Static analysis must never execute this file.');
