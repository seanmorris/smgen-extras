<?php

declare(strict_types=1);

use SmgenPhpDoc\Analyzer;

require_once(dirname(__DIR__) . '/vendor/autoload.php');

try
{
	$inputPath = $argv[1] ?? '';
	if($inputPath === '' || !is_file($inputPath) || is_link($inputPath))
	{
		throw new RuntimeException('The adapter requires a readable, non-link JSON input file.');
	}
	$contents = file_get_contents($inputPath);
	if($contents === false || strlen($contents) > 16_777_216)
	{
		throw new RuntimeException('The adapter input is unreadable or exceeds 16 MiB.');
	}
	$input = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
	if(!is_array($input)) throw new RuntimeException('The adapter input must be a JSON object.');
	$graph = (new Analyzer())->analyze($input);
	echo json_encode($graph, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES), "\n";
}
catch(Throwable $error)
{
	fwrite(STDERR, $error->getMessage() . "\n");
	exit(1);
}
