<?=$context->frontmatter(['title' => 'Products'])?>
# Products

<?php foreach($records as $record): ?>
- [<?=$context->escapeHtml($record['row']['title'])?>](<?=$context->escapeHtml($record['row']['slug'])?>.html)
<?php endforeach; ?>
