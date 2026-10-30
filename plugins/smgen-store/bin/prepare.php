<?php

declare(strict_types=1);

require_once(dirname(__DIR__) . '/src/autoload.php');

try
{
	SmgenStore\PluginHook::run();
}
catch(Throwable $error)
{
	fwrite(STDERR, 'smgen-store: ' . $error->getMessage() . "\n");
	exit(1);
}
