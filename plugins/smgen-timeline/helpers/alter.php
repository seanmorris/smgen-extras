<?php

/**
 * SMGen menu alter hook: every dated page joins each timeline menu unless it opts out.
 */

declare(strict_types=1);

return static function(array &$registry, array $catalog): void {
	$menus = [];
	foreach($registry['menus'] as $name => $definition)
	{
		if(($definition['renderer'] ?? null) === 'timeline')
		{
			$menus[] = (string) $name;
		}
	}
	if($menus === [])
	{
		return;
	}
	foreach($catalog['pages'] ?? [] as $page)
	{
		$metadata = $page['metadata'] ?? [];
		if(!array_key_exists('date', $metadata) || array_key_exists('menus', $metadata))
		{
			continue;
		}
		if(($metadata['archive'] ?? true) === false || is_array($metadata['category'] ?? null))
		{
			continue;
		}
		foreach($menus as $menu)
		{
			navigationPutPageLink($registry['links'], $menu, $page, [], 'smgen-timeline');
		}
	}
};
