<?php

/**
 * SMGen menu renderer for the timeline: compiles dated pages and renders the archive widget.
 */

declare(strict_types=1);

require_once(dirname(__DIR__) . '/src/autoload.php');

return [
	'compile' => static fn(array $menu, array $links, array $catalog, array $context): array => SmgenTimeline\Timeline::compile($menu, $links, $catalog, $context),
	'assets' => static fn(array $menu): array => SmgenTimeline\Timeline::assets($menu),
	'render' => static function(array $menu, string $idPath, array $context): void {
		SmgenTimeline\Timeline::render($menu, $idPath, $context);
	},
];
