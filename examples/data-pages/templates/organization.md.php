<?php

/**
 * Renders one synthetic organization row as an SMGen Markdown profile.
 */

echo $context->frontmatter([
	'title' => $row['name'],
	'description' => 'Synthetic organization record for ' . $row['name'] . '.',
	'TOC' => true,
	'menus' => [
		'directory' => [
			'parent' => 'organization-records',
			'weight' => (int) $row['index'],
		],
	],
]);
?>
# <?=$context->escapeHtml($row['name'])?>

<p class="record-kicker">Synthetic organization record</p>

<?=$context->escapeHtml($row['description'])?>

## Company profile

<dl class="record-details">
<dt>Organization ID</dt><dd><?=$context->escapeHtml($row['organization_id'])?></dd>
<dt>Industry</dt><dd><?=$context->escapeHtml($row['industry'])?></dd>
<dt>Country</dt><dd><?=$context->escapeHtml($row['country'])?></dd>
<dt>Founded</dt><dd><?=$context->escapeHtml($row['founded'])?></dd>
</dl>

## Size and contact

<dl class="record-details">
<dt>Employees</dt><dd><?=$context->escapeHtml($row['employees'])?></dd>
<dt>Website</dt><dd><?=$context->escapeHtml($row['website'])?></dd>
</dl>

[Back to all organizations](index.html)
