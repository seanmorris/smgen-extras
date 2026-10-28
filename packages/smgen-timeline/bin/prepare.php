<?php

declare(strict_types=1);

require_once(dirname(__DIR__) . '/src/autoload.php');

try
{
	SmgenTimeline\PluginHook::run();
}
catch(Throwable $error)
{
	fwrite(STDERR, 'smgen-timeline: ' . $error->getMessage() . "\n");
	exit(1);
}
