<?php

declare(strict_types=1);

require_once(dirname(__DIR__) . '/src/autoload.php');

try
{
	SmgenDataPages\PluginHook::run();
}
catch(Throwable $error)
{
	fwrite(STDERR, 'smgen-data-pages: ' . $error->getMessage() . "\n");
	exit(1);
}
