<?php

/**
 * Renders one synthetic customer row as an SMGen Markdown profile.
 */

$name = $row['first_name'] . ' ' . $row['last_name'];

echo $context->frontmatter([
	'title' => $name,
	'description' => 'Synthetic customer record for ' . $name . ' at ' . $row['company'] . '.',
	'TOC' => true,
	'menus' => [
		'directory' => [
			'parent' => 'customer-records',
			'weight' => (int) $row['index'],
		],
	],
]);
?>
# <?=$context->escapeHtml($name)?>

<p class="record-kicker">Synthetic customer record</p>

## Account

<dl class="record-details">
<dt>Customer ID</dt><dd><?=$context->escapeHtml($row['customer_id'])?></dd>
<dt>Company</dt><dd><?=$context->escapeHtml($row['company'])?></dd>
<dt>Location</dt><dd><?=$context->escapeHtml($row['city'])?>, <?=$context->escapeHtml($row['country'])?></dd>
<dt>Subscribed</dt><dd><?=$context->escapeHtml($row['subscription_date'])?></dd>
</dl>

## Contact

<dl class="record-details">
<dt>Email</dt><dd><?=$context->escapeHtml($row['email'])?></dd>
<dt>Primary phone</dt><dd><?=$context->escapeHtml($row['phone_primary'])?></dd>
<dt>Secondary phone</dt><dd><?=$context->escapeHtml($row['phone_secondary'])?></dd>
<dt>Website</dt><dd><?=$context->escapeHtml($row['website'])?></dd>
</dl>

[Back to all customers](index.html)
