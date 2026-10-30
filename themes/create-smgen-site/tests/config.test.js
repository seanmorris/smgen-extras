import {test} from 'node:test';
import assert from 'node:assert/strict';
import {rewriteSmgenYaml, validateRewrite, yamlScalar, readScalar} from '../lib/config.js';

const sample = [
	'PRODUCT_NAME: Plain',
	'TITLE_PREFIX: Plain',
	'TAGLINE: A site that documents its own markup',
	"BASE_URL: ''",
	'OUTPUT_DIR: ./docs',
	'',
	'RSS_FEEDS:',
	'  posts:',
	'    title: Plain',
	'    description: Posts',
	'',
].join('\n');

test('rewrites the top-level keys and nothing else', () => {
	const values = {PRODUCT_NAME: 'Harbour Notes', TITLE_PREFIX: 'Harbour Notes', BASE_URL: '', OUTPUT_DIR: './docs'};
	const after = rewriteSmgenYaml(sample, values);
	assert.match(after, /^PRODUCT_NAME: "Harbour Notes"$/m);
	assert.match(after, /^TITLE_PREFIX: "Harbour Notes"$/m);
	assert.match(after, /^BASE_URL: ''$/m);
	assert.match(after, /^OUTPUT_DIR: "\.\/docs"$/m);
	assert.match(after, /^    title: Plain$/m);
	assert.doesNotThrow(() => validateRewrite(sample, after, values));
});

test('rejects a missing or duplicated key', () => {
	assert.throws(() => rewriteSmgenYaml(sample, {MISSING: 'x'}), /exactly one top-level MISSING/);
	assert.throws(() => rewriteSmgenYaml(sample + 'PRODUCT_NAME: Again\n', {PRODUCT_NAME: 'x'}), /found 2/);
});

test('keeps CRLF line endings', () => {
	const crlf = sample.replace(/\n/g, '\r\n');
	const after = rewriteSmgenYaml(crlf, {PRODUCT_NAME: 'Windows Site'});
	assert.ok(after.includes('PRODUCT_NAME: "Windows Site"\r\n'));
	assert.equal(after.split('\r\n').length, crlf.split('\r\n').length);
});

test('round-trips awkward names', () => {
	for(const name of ['Quote " inside', 'Back\\slash', 'Ünïcode — dash', 'colon: here', '#hash'])
	{
		assert.equal(readScalar(yamlScalar(name)), name);
		const after = rewriteSmgenYaml(sample, {PRODUCT_NAME: name});
		assert.doesNotThrow(() => validateRewrite(sample, after, {PRODUCT_NAME: name}));
	}
});

test('validation catches an unrelated change', () => {
	const after = rewriteSmgenYaml(sample, {PRODUCT_NAME: 'x'}).replace('    title: Plain', '    title: Changed');
	assert.throws(() => validateRewrite(sample, after, {PRODUCT_NAME: 'x'}), /changed unexpectedly/);
});
